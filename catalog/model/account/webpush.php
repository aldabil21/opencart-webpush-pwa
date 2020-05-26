<?php
class ModelAccountWebpush extends Model {
	public function addSubscription($data) {
        $customer_id = $this->customer->getId();
		$this->db->query("INSERT INTO " . DB_PREFIX . "user_push SET customer_id = '" . (int)$customer_id . "', endpoint = '" . $data['endpoint'] . "', auth = '" . $data['keys']['auth'] . "', p256dh = '" . $data['keys']['p256dh'] . "', date_added = NOW() ");

        $saved = $this->db->getLastId();
        
		return $saved;
    }	
    public function deleteSubscription($endpoint) {
        $customer_id = $this->customer->getId();
		$deleted = $this->db->query("DELETE FROM " . DB_PREFIX . "user_push WHERE endpoint = '" . $endpoint ."'");         
		return $deleted;
    }
    public function getCustomerSubscriptions($id) {
        $sql = "SELECT * from " . DB_PREFIX . "user_push WHERE customer_id = '".(int)$id."' ";
		$query = $this->db->query($sql);
        $subscriptions = array();
        if($query->rows){
            foreach ($query->rows as $sub) {
                if($sub['endpoint']){
                    $subscriptions[] = array(
                        'endpoint' => $sub['endpoint'],
                        'keys' => array(
                            'auth' =>  $sub['auth'],
                            'p256dh' => $sub['p256dh']
                        )
                    );
                }
            }
        }
		return $subscriptions;
    }	
    public function getAdminsSubscriptions() {
        $sql = "SELECT * from " . DB_PREFIX . "user_push WHERE admin_id != '0' ";
		$query = $this->db->query($sql);
        $subscriptions = array();
        if($query->rows){
            foreach ($query->rows as $sub) {
                if($sub['endpoint']){
                    $subscriptions[] = array(
                        'endpoint' => $sub['endpoint'],
                        'keys' => array(
                            'auth' =>  $sub['auth'],
                            'p256dh' => $sub['p256dh']
                        )
                    );
                }
            }
        }
		return $subscriptions;
    }	
    
}