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
     * @var string
     */
    public $network;

    /**
     * @param bool $testnet
     */
    public function __construct(bool $testnet = false) {
        $this->testnet = $testnet;
        $this->network = $testnet ? 'testnet' : 'livenet';

        if (!$this->testnet) {
            $this->api = "https://blockstream.info/api/";
            $this->explorer = "https://blockstream.info/";
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
        $apiUrl = $this->api . 'address/' . $receiver . '/txs';
        $data = json_decode(file_get_contents($apiUrl));

        if (!$data) {
            return (object) [
                "hash" => null,
                "amount" => 0
            ];
        }
            
        $tx = $data[0];

        $index = array_search($receiver, array_column($tx->out, 'scriptpubkey_address'));

        $data = $tx->vout[$index];

        return (object) [
            "hash" => $tx->txid,
            "amount" => Utils::toDec($data->value, 8)
        ];
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