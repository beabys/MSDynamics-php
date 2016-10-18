<?php
namespace MSDynamics\Client;

use DOMDocument;
use DynamicsPhp\Client\Curl as ClientService;
use Exception;
/**
 * Class ClientBuilder
 * @package MSDynamics\Client
 */
class ClientBuilder
{

    /**
     * @var string
     */
    protected $dynamicsUrl;

    /**
     * @var null
     */
    protected $email = null;

    /**
     * @var null
     */
    protected $password = null;

    /**
     * @var null
     */
    protected $deviceUserName = null;

    /**
     * @var string
     */
    protected $orgPoint    = "/XRMServices/2011/Organization.svc";

    /**
     * @var string
     */
    protected $dynamicsRegion = 'crmapac';

    /**
     * @var string
     */
    protected $securityToken0 = '';

    /**
     * @var string
     */
    protected $securityToken1 = '';

    /**
     * @var string
     */
    protected $keyIdentifier  = '';

    /**
     * @var string
     */
    protected $deviceUsername = '';

    /**
     * @var string
     */
    protected $devicePassword = '';

    /**
     * @return string
     */
    protected function getRandomValue()
    {
        $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        $length = 24;
        $count = strlen($charset);
        while ($length--) {
            $str .= $charset[mt_rand(0, $count-1)];
        }
        return $str;
    }

    /**
     * @param Curl $curl
     * @param DOMDocument $responseDom
     * @param $currentTime
     * @param $nextTime
     * @throws Exception
     */
    protected function getSecurityTokens(ClientService $curl, DOMDocument $responseDom, $currentTime, $nextTime)
    {
        $data = [
            'UUID' => $curl->getUUID(),
            'CURRENT_TIME' => $currentTime,
            'EXPIRE_TIME' => $nextTime,
            'EMAIL' => $this->getEmail(),
            'PASSWORD' => $this->getPassword(),
            'DYNAMICS_REGION' => $this->getDynamicsRegion(),
        ];
        $content = $curl->setXML("SecurityTokens", $data);
        $response = $curl->doCurl("/RST2.srf", "login.microsoftonline.com", "https://login.microsoftonline.com/RST2.srf", $content);
        $responseDom->loadXML($response);
        $cipherValues = $responseDom->getElementsbyTagName("CipherValue");
        $keyIdentifier = $responseDom->getElementsbyTagName("KeyIdentifier");

        if (empty($cipherValues) || empty($keyIdentifier))
        {
            throw new Exception("Failed to get security tokens.");
        }

        $this->securityToken0 =  $cipherValues->item(0)->textContent;
        $this->securityToken1 =  $cipherValues->item(1)->textContent;
        $this->keyIdentifier  =  $keyIdentifier->item(0)->textContent;
    }

    /**
     * @return string
     */
    public function getDynamicsUrl()
    {
        return $this->dynamicsUrl;
    }

    /**
     * @param $dynamicsUrl
     * @return $this
     */
    public function setDynamicsUrl($dynamicsUrl)
    {
        $this->dynamicsUrl = $dynamicsUrl;

        return $this;
    }

    /**
     * @return null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return null
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return null
     */
    public function getDeviceUserName()
    {
        return $this->deviceUserName;
    }

    /**
     * @param $deviceUserName
     * @return $this
     */
    public function setDeviceUserName($deviceUserName)
    {
        $this->deviceUserName = $deviceUserName;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrgPoint()
    {
        return $this->orgPoint;
    }

    /**
     * @param $orgPoint
     * @return $this
     */
    public function setOrgPoint($orgPoint)
    {
        $this->orgPoint = $orgPoint;

        return $this;
    }

    /**
     * @return string
     */
    public function getDynamicsRegion()
    {
        return $this->dynamicsRegion;
    }

    /**
     * @param $dynamicsRegion
     * @return $this
     */
    public function setDynamicsRegion($dynamicsRegion)
    {
        $this->dynamicsRegion = $dynamicsRegion;

        return $this;
    }

    /**
     * @return string
     */
    public function getSecurityToken0()
    {
        return $this->securityToken0;
    }

    /**
     * @param $securityToken0
     * @return $this
     */
    public function setSecurityToken0($securityToken0)
    {
        $this->securityToken0 = $securityToken0;

        return $this;
    }

    /**
     * @return string
     */
    public function getSecurityToken1()
    {
        return $this->securityToken1;
    }

    /**
     * @param $securityToken1
     * @return $this
     */
    public function setSecurityToken1($securityToken1)
    {
        $this->securityToken1 = $securityToken1;

        return $this;
    }

    /**
     * @return string
     */
    public function getKeyIdentifier()
    {
        return $this->keyIdentifier;
    }

    /**
     * @param $keyIdentifier
     * @return $this
     */
    public function setKeyIdentifier($keyIdentifier)
    {
        $this->keyIdentifier = $keyIdentifier;

        return $this;
    }


    /**
     * @throws \Exception
     */
    public function build()
    {
        $client = new Client(
            $this->getKeyIdentifier(),
            $this->getSecurityToken0(),
            $this->getSecurityToken1(),
            $this->getOrgPoint(),
            $this->getDynamicsUrl()
        );
        return $client;
    }

    /**
     * @throws Exception
     */
    public function setTokens()
    {
        if (empty($this->securityToken0) && empty($this->securityToken1) && empty($this->keyIdentifier))
        {
            $curl = new ClientService();
            $responseDom  = new DOMDocument();
            $this->deviceUserName = "11" .  $this->getRandomValue();
            $this->devicePassword = $this->getRandomValue();
            $currentTime = substr(date('c'),0,-6) . ".00";
            $nextTime = substr(date('c', strtotime('+1 day')),0,-6) . ".00";
            $this->getSecurityTokens($curl, $responseDom, $currentTime, $nextTime);
        }

        return $this;
    }
}
