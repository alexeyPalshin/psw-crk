<?php

namespace Palshin\PswCrack\Service;

class RainbowTableService
{
    const SALT = 'ThisIs-A-Salt123';

//ABJIHVY	0e755264355178451322893275696586
//DQWRASX	0e742373665639232907775599582643
//DYAXWCA	0e424759758842488633464374063001
//EEIZDOI	0e782601363539291779881938479162
//GEGHBXL	0e248776895502908863709684713578
//GGHMVOE	0e362766013028313274586933780773
//GZECLQZ	0e537612333747236407713628225676
//IHKFRNS	0e256160682445802696926137988570
//MAUXXQC	0e478478466848439040434801845361
//MMHUWUV	0e701732711630150438129209816536
//NOOPCJF	0e818888003657176127862245791911
//NWWKITQ	0e763082070976038347657360817689
//PJNPDWY	0e291529052894702774557631701704
//QLTHNDT	0e405967825401955372549139051580
//QNKCDZO	0e830400451993494058024219903391

    public function generateRainbowTable($writeService, $dataProvider)
    {
        $values = [];
        foreach ($dataProvider->generate() as $k => $value) {
            if (null !== $value) {
                $values[] = ['value' => $value, 'hash' => md5($value)];
//                $writeService->writeLine($this->format($value));
            }

            if($k % 5000 === 0) {
                $writeService->connection('db')->table('first_half')->insert($values);
                $values = [];
            }
        }
    }

    public function lookupHash($fileService, $hash)
    {
        $returnResult = [];

        $fileService->reset();

        while ('' !== $line = $fileService->readLine()) {
            $data = $this->unformat($line);

            if ($hash === current($data)) {
                return $data;
            }

            if (false !== strpos(current($data), $hash)) {
                $returnResult += $data;
            }
        }

        return $returnResult;
    }

    public function format($value)
    {
        $hashedValue = md5($value.self::SALT);

        return sprintf("%s,%s", $hashedValue, $value);
    }

    public function unformat($formattedValue)
    {
        $data = explode(',', (string) $formattedValue, 2);

        return [$data[1] => $data[0]];
    }
}