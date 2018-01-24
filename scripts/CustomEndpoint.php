<?php

class CustomEndpoint {

    private $endpointName;
    private $endpointUsers = array();
    private $endpointParams = array();

    /**
     * @return mixed
     */
    public function getEndpointName()
    {
        return $this->endpointName;
    }

    /**
     * @return array
     */
    public function getEndpointParams()
    {
        return $this->endpointParams;
    }

    /**
     * @return array
     */
    public function getEndpointUsers()
    {
        return $this->endpointUsers;
    }

    public function addEndpointUser($userLevelID) {
        array_push($endpointUsers, $userLevelID);
    }

    public function addEndpointParam($endpointParamName) {
        array_push($endpointParams, $endpointParamName);
    }


}

?>