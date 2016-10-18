<?php

namespace MSDynamics\Client;

use DynamicsPhp\Client\Curl as ClientService;
use DOMDocument;
use Exception;

/**
 * Class Client
 * @package MSDynamics\Client
 */
class Client
{

    /**
     * @var string
     */
    protected $keyIdentifier  = '';

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
    protected $orgPoint = '';

    /**
     * @var string
     */
    protected $dynamicsUrl = '';

    /**
     * @var array
     */
    protected $valuesForSchema = [
        'optionsetvalue' => 'OptionSetValue',
        'money' => 'Money'
    ];

    /**
     * @var Curl|null
     */
    protected $curl = null;


    /**
     * @param $keyIdentifier
     * @param $securityToken0
     * @param $securityToken1
     * @param $orgPoint
     * @param $dynamicsUrl
     */
    public function __construct($keyIdentifier, $securityToken0, $securityToken1, $orgPoint, $dynamicsUrl)
    {
        $this->curl = new ClientService();
        $this->keyIdentifier = $keyIdentifier;
        $this->securityToken0 = $securityToken0;
        $this->securityToken1 = $securityToken1;
        $this->orgPoint = $orgPoint;
        $this->dynamicsUrl = $dynamicsUrl;
    }

    /**
     * @param $method
     * @return mixed|string
     * @throws Exception
     */
    protected function getHeader($method)
    {
        $curl = $this->curl;
        $currentTime = substr(date('c'),0,-6) . ".00";
        $nextTime = substr(date('c', strtotime('+1 day')),0,-6) . ".00";
        $data = [
            'ACTION' => $method,
            'UUID' => $curl->getUUID(),
            'CURRENT_TIME' => $currentTime,
            'EXPIRE_TIME' => $nextTime,
            'KEY_IDENTIFIER' => $this->keyIdentifier,
            'SECURITY_TOKEN_0' => $this->securityToken0,
            'SECURITY_TOKEN_1' => $this->securityToken1,
            'DYNAMIC_URL' => $this->dynamicsUrl . $this->orgPoint
        ];
        return $curl->setXML("Header", $data);
    }

    /**
     * @param $data
     * @return string
     */
    protected function getAttributes($data)
    {
        $attributes = '';
        foreach ($data as $valueData) {
            if (isset($valueData['key']) && isset($valueData['type']) && isset($valueData['value'])) {
                $type = 'd:' . $valueData['type'];
                $value = $valueData['value'];
                $schema = ' xmlns:d="http://www.w3.org/2001/XMLSchema"';
                if (array_key_exists(strtolower($valueData['type']), $this->valuesForSchema)) {
                    $valueForSchema = $this->valuesForSchema;
                    $type = 'b:' . $valueForSchema[strtolower($valueData['type'])];
                    $schema = '';
                    $value = '<b:Value>' . $valueData['value'] . '</b:Value>';
                }
                $attributes .= '<b:KeyValuePairOfstringanyType>
                    <c:key>' . $valueData['key'] . '</c:key>
                    <c:value i:type="' . $type . '"' . $schema . '>' . $value . '</c:value>
                </b:KeyValuePairOfstringanyType>';
            }
        }
        return $attributes;
    }

    /**
     * @param $method
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function call($method, $params)
    {
        $header = $this->getHeader($method);
        if (!method_exists($this, 'getAttributes' . $method)) {
            throw new Exception(sprintf('Method %s doesn\'t exist', $method));
        }
        $body = $this->{'getAttributes' . $method}($params);
        $data = [
            'HEADER' => $header,
            'BODY' => $body
        ];
        $xml = $this->curl->setXML('Body', $data);
        return $this->curl->doCurl($this->orgPoint, $this->dynamicsUrl, "https://".$this->dynamicsUrl . $this->orgPoint, $xml);

    }

    /**
     * @param $response
     * @return mixed
     * @throws Exception
     */
    public function handleResult($response)
    {
        $responseDom  = new DOMDocument();
        $responseDom->loadXML($response);
        $result = $responseDom->getElementsbyTagName("CreateResult");
        if (!empty($result))
        {
            return $result->item(0)->textContent;
        }
        throw new Exception($result);
    }

    /**
     * @param $data
     * @return mixed|string
     * @throws Exception
     */
    protected function getAttributesCreate($data)
    {
        $createData = [
            'ATTRIBUTES' => $this->getAttributes($data['fields']),
            'LOGICAL_NAME' => $data['logical_name']
        ];
        return $this->curl->setXML('Create', $createData);
    }

    /**
     * @param $data
     * @return mixed|string
     * @throws Exception
     */
    protected function getAttributesUpdate($data)
    {
        $createData = [
            'LOGICAL_NAME' => $data['logical_name'],
            'GUID' => $data['guid'],
            'ATTRIBUTES' => $this->getAttributes($data['fields']),
        ];
        return $this->curl->setXML('Update', $createData);
    }
}
