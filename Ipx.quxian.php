<?php

/* 区县级 IP 地址库查询类 by pAUL gAO <gaochunhui@gmail.com> */

class IPX_Quxian
{
    private static $ip     = NULL;

    private static $fp     = NULL;
    private static $offset = NULL;
    private static $index  = NULL;

    public function __destruct()
    {
        if (self::$fp !== NULL)
        {
            fclose(self::$fp);
        }
    }

    private static function init()
    {
        if (self::$fp === NULL)
        {
            self::$ip = new self();

            self::$fp = fopen(__DIR__ . '/quxian.datx', 'rb');
            if (self::$fp === FALSE)
            {
                return 'Invalid IP Quxian file';
            }

            self::$offset = unpack('Nlen', fread(self::$fp, 4));
            if (self::$offset['len'] < 4)
            {
                return 'Invalid IP Quxian file';
            }

            self::$index = fread(self::$fp, self::$offset['len'] - 4);
        }
    }

    public static function find($ip)
    {
        $nip   = gethostbyname($ip);
        $ipdot = explode('.', $nip);

        $ipdot[0] = (int)$ipdot[0];

        if (self::$fp === NULL)
        {
            self::init();
        }

        $nip = pack('N', ip2long($nip));

        $tmp_offset = $ipdot[0] * 256 + intval($ipdot[1]);
        $tmp_offset = $tmp_offset * 4;
        $start      = unpack('Vlen', self::$index[$tmp_offset] . self::$index[$tmp_offset + 1] . self::$index[$tmp_offset + 2] . self::$index[$tmp_offset + 3]);

        $index_offset = $index_length = [];
        $loop = 0;

        for ($start = $start['len'] * 13 + 262144; $start < self::$offset['len'] - 262148; $start += 13)
        {
            if ((self::$index{$start} . self::$index{$start + 1} . self::$index{$start + 2} . self::$index{$start + 3}) <= $nip)
            {
                $loop++;

                if ($nip <= (self::$index{$start + 4} . self::$index{$start + 5} . self::$index{$start + 6} . self::$index{$start + 7}))
                {
                    $index_offset = unpack('Vlen', self::$index{$start + 8} . self::$index{$start + 9} . self::$index{$start + 10} . self::$index[$start + 11]);
                    $index_length = unpack('Clen', self::$index{$start + 12});

                    break;
                }
            }
            else
            {
                break;
            }
        }

        if ($index_offset === [])
        {
            return FALSE;
        }

        fseek(self::$fp, self::$offset['len'] + $index_offset['len'] - 262144);

        return explode("\t", fread(self::$fp, $index_length['len']));
    }
}

?>