<?php
require(DIR_UPLOAD_VENDOR . '/autoload.php');
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class ControllerWebpushWebpush extends Controller {
   
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
                    $result['success'] = 'Successfully Sbscribed. You will recieve notifications';
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
    public function notify($push) { //To notify customer from your admin panel, for example when change order status

        $close_action = array(
            'action'=> "close",
            'title'=> "Close",
            // 'icon'=> "images/checkmark.png"
        );
        $id = $push['id'];
        $title = isset($push['title']) ? $push['title'] : $this->config->get('config_name');
        $body = $push['msg'];
        $icon = isset($push['icon']) ? $push['icon'] : "image/catalog/push-icon.png"; //change your fallback icon path accordingly
        $badge = isset($push['badge']) ? $push['badge'] : "image/catalog/push-badge.png"; //change your fallback icon path accordingly
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

        $this->load->model('account/webpush');
        $subs = $this->model_account_webpush->getCustomerSubscriptions($id);
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
                if($sub['endpoint']){
                    $subscription = Subscription::create($sub);
                    $res = $webPush->sendNotification(
                        $subscription,
                        json_encode($payload)
                    );
                }
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

}