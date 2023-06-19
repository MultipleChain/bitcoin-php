<?php 

namespace MultipleChain\Bitcoin;

use MultipleChain\Utils;

class Transaction {

    /**
     * @var object
     */
    private $provider;

    /**
     * @var string
     */
    private $hash;

    /**
     * @var object
     */
    private $data;

    /**
     * @param string $hash
     * @param Provider $provider 
     * @return void
     */
    public function __construct(string $hash, Provider $provider) 
    {
        $this->hash = $hash;
        $this->provider = $provider;
    }

    /**
     * @return string
     */
    public function getHash() : string
    {
        return $this->hash;
    }

    /**
     * @return object
     */
    public function getData() : object 
    {
        try {
            $txApi = $this->provider->api . 'tx/' . $this->hash;
            $this->data = json_decode(file_get_contents($txApi));
        } catch (\Exception $e) {
            throw new \Exception('There was a problem retrieving transaction data!');
        }

        return $this->data;
    }

    /** 
     * @return int
     */
    public function getConfirmations() : int 
    {
        try {

            $this->getData();
            $blockApi = $this->provider->api . 'blocks/tip/height';
            $latestBlock = json_decode(file_get_contents($blockApi));

            if (is_object($latestBlock)) {
                $latestBlock = $latestBlock->height;
            }

            if (isset($this->data->status->block_height)) {
                $blockHeight = $this->data->status->block_height;
            } else {
                return 0;
            }

            return (($latestBlock - $blockHeight) + 1);
        } catch (\Exception $e) {}
    }

    /**
     * @return ?bool
     */
    public function validate() : ?bool
    {
        $this->getData();
        $result = null;

        if ($this->data == null) {
            $result = false;
        } else {
            if (isset($this->data->status->block_height) && $this->data->status->block_height) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * @param object $config
     * @return bool
     */
    public function verifyTransferWithData(object $config) : bool
    {
        if ($this->validate()) {

            
            $index = array_search($config->receiver, array_column($this->data->vout, 'scriptpubkey_address'));

            $data = $this->data->vout[$index];
            
            $data = (object) [
                "receiver" => $data->scriptpubkey_address,
                "amount" => Utils::toDec($data->value, 8)
            ];

            if ($data->receiver == $config->receiver && $data->amount == $config->amount) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @return string
     */
    public function getUrl() {
        return $this->provider->explorer . 'tx/' . $this->hash;
    }
}