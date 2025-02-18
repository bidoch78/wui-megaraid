<?php 

    declare(strict_types=1);

    require_once(__DIR__ . "/../code/autoload.php");

    use megaraid\controllers\physicaldrive;
		
    $inquiry = [
    "     WD-WMAYW0460548WDC WD2500AAKX-753CA1                   19.01H19",
    "     WD-WCAV50907274WDC WD10EADS-00M2B0                     01.00A01",
    "161768402151        SanDisk SDSSDA240G                      Z22000RL",
    "1843E160D98E        CT240BX500SSD1                           M6CR013",
    "            W0Q0FR1NST320LT014-9YK142                       0001DEM7", // SEAGATE
    "6RYC65JJ            ST3250410AS                             3.AHC   ", //SEAGATE
    "100424PBPB01ECCP4X3LHitachi HTS545016B9A300                 PBBOC60S",
    "SEAGATE SMKR6000S5xeN7.23P00Z4D2KN5P                                ", // SPECIAL
    "Pliant  LB406SC         MS0740502148    tag/T3CH                    ",  // SPECIAL
    "SEAGATE SMKR6000S5xeN7.23P00Z4D2M0PA                                ",  // SPECIAL
    "BTTV410202U2100FGN  MK0100GCTYU                             5DV1HPG4",
    "E095071B06CE00325284SATA SSD                                SBFM61.5" // ??? integral no name
    ];

    foreach($inquiry as $i) {
        $data = physicalDrive::readInquiryData($i);
        echo "-----------------------------------------------\n";
        //echo $i . "\n";
        echo $i . " - Vendor: " . $data["vendor"] .  ", S/N: " . $data["serial_number"] . ", Model: " . $data["model"] . ", Firmware: " . $data["firmware"] . "\n";
    }

?>