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
     * @param array|object $options
     */
    public function __construct($options) {
        
        $options = is_array($options) ? (object) $options : $options;
        $this->testnet = isset($options->testnet) ? $options->testnet : false;
        $this->network = $this->testnet ? 'testnet' : 'livenet';

        if (!$this->testnet) {
            $this->api = "https://blockstream.info/api/";
            $this->explorer = "https://blockstream.info/";
        } else {
            $this->api = "https://blockstream.info/testnet/api/";
            $this->explorer = "https://blockstream.info/testnet/";
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