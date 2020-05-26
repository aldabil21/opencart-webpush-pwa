<?php
require(DIR_UPLOAD_VENDOR . '/autoload.php');
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class ControllerApiWebpush extends Controller {
   
    public function subscribe() {
        $this->load->model('account/webpush');
        $method = $this->request->server['REQUEST_METHOD'];
        $subscription_detail = json_decode(html_entity_decode($this->request->post['subscription']), true);
        $result = array();
     
        if($method == 'POST'){
            if(!$subscription_detail['endpoint']){
                $result['error'] = 'Error: Faild to subscribe. please refresh and try again';
            }
            if($subscription_detail['endpoint']){
                $saved = $this->model_account_webpush->addSubscription($subscription_detail);
                if($saved){
                    $pushData = array(
                        'id' =>$this->customer->getId(),
                        'title' => "Subscription Success",
                        'msg' =>"Congratulations, you've got it right!",
                    );
                    $this->notify($pushData);
                }else{
                    $result['error'] = 'Error Saving Subscription: please refresh and try again';
                }
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($result));
    }
    public function unsubscribe(){
        $this->load->model('account/webpush');
        $method = $this->request->server['REQUEST_METHOD'];
        $subscription_detail = json_decode(html_entity_decode($this->request->post['subscription']), true);
        $result = array();
        
        if($method == 'POST'){   
            if($subscription_detail['endpoint']){
                $deleted = $this->model_account_webpush->deleteSubscription($subscription_detail['endpoint']);
                if($deleted){
                    $result['success'] = 'Successfully unsbscribed. You will not recieve notifications anymore';
                }else{
                    $result['error'] = 'Error Deleting Subscription: please refresh and try again';
                }
            }else{
                $result['error'] = 'Error: Faild to unsubscribe. please refresh and try again';
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($result));
    }
    public function notify($push) {
        // echo"<pre>";print_r($push);die;
        //Webpush content
        $close_action = array(
            'action'=> "close",
            'title'=> "Close",
            // 'icon'=> "images/cancel.png"
        );
        $id = $push['id'];
        $title = isset($push['title']) ? $push['title'] : $this->config->get('config_name');
        $body = $push['msg'];
        $icon = isset($push['icon']) ? $push['icon'] : "https://picsum.photos/300/300"; //change your fallback icon path accordingly
        $badge = isset($push['badge']) ? $push['badge'] : "https://picsum.photos/300/300";//change your fallback badge accordingly
        $vibrate = isset($push['vibrate']) ? $push['vibrate'] : [100, 50, 100]; //I think this is deprecated?
        $data = isset($push['data']) ? $push['data'] : '';
        $dir = isset($push['dir']) ? $push['dir'] : 'auto';
        $image = isset($push['image']) ? $push['image'] : '';
        $action[] = isset($push['action']) ? $push['action'] : null;
        $action[] = $close_action;
        $final_actions = array_values(array_filter($action));
        $payload = array(
            'title' => $title,
            'body' => $body,
            'icon' => $icon,
            'badge' => $badge,
            'vibrate' => $vibrate,
            'data' => $data,
            'dir' => $dir,
            'image' => $image,
            'actions' => $final_actions,
        );

        //Get user subscriptions
        $this->load->model('account/webpush');
        $subs = $this->model_account_webpush->getCustomerSubscriptions($id);
        $result = array();
        if($subs){
            $pushAuth = array(
                'VAPID' => array(
                'subject' => $this->config->get('site_ssl'),
                'publicKey' => PUSH_PUBLIC, //In your config.php
                'privateKey' => PUSH_PRIVATE, //In your config.php
                ),
            );
            $webPush = new WebPush($pushAuth);
            foreach($subs as $sub){
                $subscription = Subscription::create($sub);
                $res = $webPush->sendNotification(
                    $subscription,
                    json_encode($payload)
                );
            }
            // handle eventual errors here, and remove the subscription from your server if it is expired
            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();
    
                if ($report->isSuccess()) {
                    $result['result'] = "[v] Message sent successfully for subscription {$endpoint}.";
                } else {
                    $result['result'] = "[x] Message failed to sent for subscription {$endpoint}: {$report->getReason()}";
                }
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($result));
    }
    public function notifyAdmin($push) {        

        //Webpush content
        $close_action = array(
            'action'=> "close",
            'title'=> "Close",
            // 'icon'=> "images/cancel.png"
        );
        $title = isset($push['title']) ? $push['title'] : $this->config->get('config_name');
        $body = $push['msg'];
        $icon = isset($push['icon']) ? $push['icon'] : "https://picsum.photos/300/300";
        $badge = isset($push['badge']) ? $push['badge'] : "https://picsum.photos/300/300";
        $vibrate = isset($push['vibrate']) ? $push['vibrate'] : [100, 50, 100];
        $data = isset($push['data']) ? $push['data'] : '';
        $dir = isset($push['dir']) ? $push['dir'] : 'auto';
        $image = isset($push['image']) ? $push['image'] : '';
        $action[] = isset($push['action']) ? $push['action'] : null;
        $action[] = $close_action;
        $final_actions = array_values(array_filter($action));
        $payload = array(
            'title' => $title,
            'body' => $body,
            'icon' => $icon,
            'badge' => $badge,
            'vibrate' => $vibrate,
            'data' => $data,
            'dir' => $dir,
            'image' => $image,
            'actions' => $final_actions,
        );

        //Get admins subscriptions
        $this->load->model('account/webpush');
        $subs = $this->model_account_webpush->getAdminsSubscriptions();
        $result = array();
        if($subs){
            $pushAuth = array(
                'VAPID' => array(
                'subject' => $this->config->get('site_ssl'),
                'publicKey' => PUSH_PUBLIC,
                'privateKey' => PUSH_PRIVATE,
                ),
            );
            $webPush = new WebPush($pushAuth);
            foreach($subs as $sub){
                $subscription = Subscription::create($sub);
                $res = $webPush->sendNotification(
                    $subscription,
                    json_encode($payload)
                );
            }
            // handle eventual errors here, and remove the subscription from your server if it is expired
            foreach ($webPush->flush() as $report) {
                $endpoint = $report->getRequest()->getUri()->__toString();
    
                if ($report->isSuccess()) {
                    $result['result'] = "[v] Message sent successfully for subscription {$endpoint}.";
                } else {
                    $result['result'] = "[x] Message failed to sent for subscription {$endpoint}: {$report->getReason()}";
                }
            }
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($result));
    }

    //Just test
    public function testcustomer() {
        $pushData = array(
            'id' => $this->customer->getId(), //customer id (required)
            'title' => "Hello Customer", //(optional: see fallback in webpush controller)
            'msg' => "Push body for customer push", //(required)
            'icon' => 'https://picsum.photos/300/300', //(optional: see fallback in webpush controller)
            'badge' => 'https://picsum.photos/300/300', //(optional: see fallback in webpush controller) 
            'vibrate' => [100, 50, 100], //(optional: see fallback in webpush controller)
            'data' => 'https://twitter.com/aldabil21', //(optional: see fallback in webpush controller)
            'dir' => 'ltr', //(optional: see fallback in webpush controller)
            'image' =>'https://picsum.photos/300/300', //(optional: see fallback in webpush controller)
            'action' => array('action'=> 'action', 'title'=>'My Twitter')
        );
        $this->notify($pushData);
    }
    public function testadmin() {
        $pushData = array(
            'title' => "Hello Admin", //(optional: see fallback in webpush controller)
            'msg' => "Push body for admin push", //(required)
            'data' => '/admin', //(optional: see fallback in webpush controller)
            'action' => array('action'=> 'action', 'title'=>'Admin Panel')
        );
        $this->notifyAdmin($pushData);
    }

}