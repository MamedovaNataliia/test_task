<?php
include __DIR__ . '/vendor/autoload.php';

use Parser\Objects\Stream;
use Parser\Objects\Reader;
use Parser\Objects\Execute;
use Parser\Objects\Writer;

try {
    if (!isset($argv[1])) {
        throw new TypeError('Argument passed must be a file path');
    }
    $path = $argv[1];
    $need_header = [
        'ID',
        'PARENT_ID',
        'EMAIL',
        'CARD',
        'PHONE'
    ];
    $obStreamRead = new Stream($path);
    $obReader = new Reader($obStreamRead, $need_header);

    $obStreamWrite = new Stream(__DIR__ . '\test.csv', 'w');
    $obWriter = new Writer($obStreamWrite);

    $obExecute = new Execute($obWriter, $obReader);
    $obExecute->run();

    $obStreamWrite->setCloseStream();
    $obStreamRead->setCloseStream();

} catch (TypeError $ex) {
    print ($ex->getMessage());
} catch (Exception $ex) {
    print ($ex->getMessage());
}

