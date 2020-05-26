<?php
class ModelAccountWebpush extends Model {
	public function addSubscription($data) {
        $admin_id = $this->user->getId();
		$this->db->query("INSERT INTO " . DB_PREFIX . "user_push SET admin_id = '" . (int)$admin_id . "', endpoint = '" . $data['endpoint'] . "', auth = '" . $data['keys']['auth'] . "', p256dh = '" . $data['keys']['p256dh'] . "', date_added = NOW() ");

        $saved = $this->db->getLastId();
        
		return $saved;
    }	
    public function deleteSubscription($endpoint) {
        $admin_id = $this->user->getId();
		$deleted = $this->db->query("DELETE FROM " . DB_PREFIX . "user_push WHERE endpoint = '" . $endpoint ."'");         
		return $deleted;
    }
    public function getCustomerSubscriptions($id) {
        $sql = "SELECT * from " . DB_PREFIX . "user_push WHERE customer_id = '".(int)$id."' ";
		$query = $this->db->query($sql);
        $subscriptions = array();
        if($query->rows){
            foreach ($query->rows as $sub) {
                $subscriptions[] = array(
                    'endpoint' => $sub['endpoint'],
                    'keys' => array(
                        'auth' =>  $sub['auth'],
                        'p256dh' => $sub['p256dh']
                    )
                );
            }
        }
		return $subscriptions;
	}			
}