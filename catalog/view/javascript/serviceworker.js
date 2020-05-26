let isSubscribed = false;

//Service worker register
if ("serviceWorker" in navigator) {
  window.addEventListener("load", function () {
    navigator.serviceWorker.register("/sw.js").then(
      function (registration) {
        // Registration was successful
        console.log(
          "ServiceWorker registration successful with scope: ",
          registration.scope
        );
      },
      function (err) {
        // registration failed :(
        console.log("ServiceWorker registration failed: ", err);
      }
    );
  });
}

//Seller Subscription
const applicationServerKey =
  "BI5PjOjLjyaQSOsad3tuzM8c5DsxN7GwYn4GeJk-Kig3WVFSfBtOm5E2_l-Y2GaGsvuC0qM7KaalgJse8HmRH78";
const runPushCheck = () => {
  //Check support
  if (!("serviceWorker" in navigator)) {
    console.warn("Sorry: browser doesn't support servieworker");
    changePushButtonState("disable");
    return;
  }

  if (!("PushManager" in window)) {
    console.warn("Sorry: browser doesn't support webpush");
    changePushButtonState("disable");
    return;
  }

  if (!("showNotification" in ServiceWorkerRegistration.prototype)) {
    console.warn("Sorry: browser doesn't support showing webpush");
    changePushButtonState("disable");
    return;
  }

  // Check the current Notification permission.
  if (Notification.permission === "denied") {
    console.warn(
      "Sorry: You've disabled notification permission manually, you may re-enable them in browser settings"
    );
    isSubscribed = false;
    changePushButtonValue("off");
    return;
  }
  if (Notification.permission === "granted") {
    //make sure if granted but unsubscribed
    navigator.serviceWorker
      .getRegistration()
      .then((registration) => {
        if (!registration) {
          isSubscribed = false;
          changePushButtonValue("off");
          return;
        }
        return registration.pushManager.getSubscription();
      })
      .then((subscription) => {
        if (!subscription) {
          isSubscribed = false;
          changePushButtonValue("off");
          return;
        }
        isSubscribed = true;
        changePushButtonValue("on");
        //TODO Maybe PUT request to update subscription? Or check if this subscription endpoint is actually in database
        return;
      })
      .catch((err) => {
        return;
      });
  }
};

function push_subscribe() {
  changePushButtonState("disable");
  return checkNotificationPermission()
    .then(() => navigator.serviceWorker.ready)
    .then((serviceWorkerRegistration) =>
      serviceWorkerRegistration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(applicationServerKey),
      })
    )
    .then((subscription) => {
      // Success: Send Subscription to server
      return subscribeToServer(subscription);
    })
    .then((subscription) => subscription && changePushButtonState("enabled")) // update UI
    .catch((e) => {
      //Error: show relevent error msg
      if (Notification.permission === "denied") {
        canceledByUserNotice();
      } else {
        canceledByUnknownNotice();
      }
    });
}
async function push_unsubscribe() {
  changePushButtonState("disable");
  try {
    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();
    if (!subscription) {
      //No subscription registered
      noticeAndOffBtn("We did not detect previous registration in browser");
      return;
    }
    const serverUnsubscription = await unsubscribeToServer(subscription);
    if (serverUnsubscription.success) {
      await subscription.unsubscribe();
    }
  } catch (err) {
    noticeAndOnBtn("Error unsubscribing, please try again");
  }
}

//Helpers
function checkNotificationPermission() {
  return new Promise((resolve, reject) => {
    if (Notification.permission === "denied") {
      return reject(new Error("Push messages are blocked."));
    }

    if (Notification.permission === "granted") {
      return resolve();
    }

    if (Notification.permission === "default") {
      return Notification.requestPermission().then((result) => {
        if (result !== "granted") {
          reject(new Error("Bad permission result"));
        }

        resolve();
      });
    }
  });
}
function changePushButtonState(state) {
  //state = (string)enable||disable
  $("#push_switcher").bootstrapToggle(state);
}
function changePushButtonValue(value) {
  //value = (string)on||off
  changePushButtonState("enable");
  $("#push_switcher").bootstrapToggle(value);
}
function canceledByUserNotice() {
  $(".push-content-modal").text(
    "Sorry: You've disabled notification permission manually, you may re-enable them in browser settings"
  );
  $("#push-result-modal").modal("show");
  changePushButtonValue("off");
}
function canceledByUnknownNotice() {
  $(".push-content-modal").text("Unknwon error: Please refresh and try again");
  $("#push-result-modal").modal("show");
  changePushButtonValue("off");
}
function noticeAndOffBtn(msg) {
  $(".push-content-modal").text(msg);
  $("#push-result-modal").modal("show");
  isSubscribed = false;
  changePushButtonValue("off");
}
function noticeAndOnBtn(msg) {
  $(".push-content-modal").text(msg);
  $("#push-result-modal").modal("show");
  isSubscribed = true;
  changePushButtonValue("on");
}
function urlBase64ToUint8Array(base64String) {
  const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
  const base64 = (base64String + padding)
    .replace(/\-/g, "+")
    .replace(/_/g, "/");

  const rawData = window.atob(base64);
  const outputArray = new Uint8Array(rawData.length);

  for (let i = 0; i < rawData.length; ++i) {
    outputArray[i] = rawData.charCodeAt(i);
  }
  return outputArray;
}
//Requests
function subscribeToServer(subscription) {
  $.ajax({
    url: "index.php?route=api/webpush/subscribe",
    type: "POST",
    cache: false,
    data: { subscription: JSON.stringify(subscription) },
    dataType: "json",
    beforeSend: function () {
      changePushButtonState("disable");
    },
    complete: function () {
      changePushButtonState("enable");
    },
    success: function (json) {
      if (json["error"]) {
        noticeAndOffBtn(json["error"]);
        return;
      }
      isSubscribed = true;
    },
    error: function (err) {
      noticeAndOffBtn("Network Error: Please refresh and try again");
      // console.log(err);
    },
  });
}
function unsubscribeToServer(subscription) {
  return $.ajax({
    url: "index.php?route=api/webpush/unsubscribe",
    type: "POST",
    cache: false,
    data: { subscription: JSON.stringify(subscription) },
    dataType: "json",
    beforeSend: function () {
      changePushButtonState("disable");
    },
    complete: function () {
      changePushButtonState("enable");
    },
    success: function (json) {
      if (json["error"]) {
        noticeAndOnBtn(json["error"]);
        return;
      }
      if (json["success"]) {
        noticeAndOffBtn(json["success"]);
        return;
      }
    },
    error: function (err) {
      noticeAndOffBtn("Network Error: Please refresh and try again");
      // console.log(err);
    },
  });
}
