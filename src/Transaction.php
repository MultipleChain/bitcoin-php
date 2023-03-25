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

            if ($this->provider->testnet) {
                $txApi = $this->provider->api . 'tx/' . $this->hash;
            } else {
                $txApi = $this->provider->api . 'rawtx/' . $this->hash;
            }
            
            if (!$this->data) {
                $this->data = json_decode(file_get_contents($txApi));
            }
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

            if ($this->provider->testnet) {
                $blockApi = $this->provider->api . 'blocks/tip/height';
            } else {
                $blockApi = $this->provider->api . 'latestblock';
            }

            $latestBlock = json_decode(file_get_contents($blockApi));

            if (is_object($latestBlock)) {
                $latestBlock = $latestBlock->height;
            }

            if ($this->provider->testnet) {
                if (isset($this->data->status->block_height)) {
                    $blockHeight = $this->data->status->block_height;
                } else {
                    return 0;
                }
            } else {
                $blockHeight = $this->data->block_height;
            }

            return (($latestBlock - $blockHeight) + 1);
        } catch (\Exception $e) {}
    }

    /**
     * @return bool
     */
    public function validate() : bool
    {
        try {

            $this->getData();

            $result = null;

            if ($this->data == null) {
                $result = false;
            } else {
                if ($this->provider->testnet) {
                    if (isset($this->data->status->block_height)) {
                        $result = true;
                    }
                } else {
                    if ($this->data->block_height) {
                        $result = true;
                    }
                }
            }

            if (is_bool($result)) {
                return $result;
            } else {
                return $this->validate();
            }

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param object $config
     * @return bool
     */
    public function verifyTransferWithData(object $config) : bool
    {
        if ($this->validate()) {

            if ($this->provider->testnet) {

                $index = array_search($config->receiver, array_column($this->data->vout, 'scriptpubkey_address'));

                $data = $this->data->vout[$index];
                
                $data = (object) [
                    "receiver" => $data->scriptpubkey_address,
                    "amount" => Utils::toDec($data->value, 8)
                ];
            } else {
                
                $index = array_search($config->receiver, array_column($this->data->out, 'addr'));

                $data = $this->data->out[$index];

                $data = (object) [
                    "receiver" => $data->addr,
                    "amount" => Utils::toDec($data->value, 8)
                ];
            }

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
        if ($this->provider->testnet) {
            return $this->provider->explorer . 'tx/' . $this->hash;
        } else {
            return $this->provider->explorer . 'transactions/btc/' . $this->hash;
        }
    }
}