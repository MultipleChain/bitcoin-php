<?php

namespace MultipleChain\Bitcoin;

use MultipleChain\Utils;

class Provider {

    /**
     * @var string
     */
    public $api;

    /**
     * @var string
     */
    public $explorer;
    
    /**
     * @var bool
     */
    public $testnet;

    /**
     * @param bool $testnet
     */
    public function __construct(bool $testnet = false) {
        $this->testnet = $testnet;

        if (!$this->testnet) {
            $this->api = "https://blockchain.info/";
            $this->explorer = "https://www.blockchain.com/explorer/";
        } else {
            $this->api = "https://blockstream.info/testnet/api/";
            $this->explorer = "https://blockstream.info/testnet/";
        }
    }

    /**
     * @param string $receiver
     * @return object
     */
    public function getAddressLastTransaction(string $receiver) : object
    {
        if ($this->testnet) {
            $apiUrl = $this->api . 'address/' . $receiver . '/txs';
        } else {
            $apiUrl = $this->api . 'rawaddr/' . $receiver;
        }

        $data = json_decode(file_get_contents($apiUrl));

        if (!$data) {
            return (object) [
                "hash" => null,
                "amount" => 0
            ];
        }

        if (isset($data->txs)) {

            $tx = $data->txs[0];

            $index = array_search($receiver, array_column($tx->out, 'addr'));

            $data = $tx->out[$index];
            
            return (object) [
                "hash" => $tx->hash,
                "amount" => Utils::toDec($data->value, 8)
            ];
        } else {
            
            $tx = $data[0];

            $index = array_search($receiver, array_column($tx->out, 'scriptpubkey_address'));

            $data = $tx->vout[$index];

            return (object) [
                "hash" => $tx->txid,
                "amount" => Utils::toDec($data->value, 8)
            ];
        }
    }

    /**
     * @param string $hash
     * @return Transaction
     */
    public function Transaction(string $hash) : Transaction
    {
        return new Transaction($hash, $this);
    }
}