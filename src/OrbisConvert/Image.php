<?php

namespace OrbisConvert;

class Image
{
    protected $config;

    public function getConfig($name = null, $param = null)
    {
        if (is_null($this->config)) {
            $class =str_replace('\\', '.', __CLASS__);
            $configFile = ROOT_DIR . '/config/' . $class. '.php';
            if (file_exists($configFile)) {
                $this->config = require ($configFile);
            } else {
                throw new \RuntimeException('Config file not exists');
            }
        }
        if (empty($name)) {
            return $this->config;
        }
        if (empty($param)) {
            if (isset ($this->config[$name])) {
                return $this->config[$name];
            }
            return null;
        }
        if (isset($this->config[$name]) && isset($this->config[$name][$param])) {
            return $this->config[$name][$param];
        }
        return null;
    }


    public function headerAccel($file)
    {
        if (headers_sent()) {
            throw new Exception('Headers alrady send');
        }
        $httpFile = \Router\Router::getUrlNormal();
        $finfo = new \finfo(FILEINFO_MIME);
        $mime = $finfo->file($file);
        header("Content-Type: $mime");
        //cache
        $seconds_to_cache = 60*60*24*36*3;
        $ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
        header("Expires: $ts");
        header("Pragma: cache");
        header("Cache-Control: max-age=$seconds_to_cache");
        header("X-Accel-Redirect: $httpFile");
    }

    public function getParams()
    {
        $url = \Router\Router::getUrlNormal();
        $urlArray = explode('/', $url);
        if (count($urlArray)<5) {
            throw new Exception('Bad params count');
        }
        $params = [
            'server' => $urlArray[2],
            'pattern' => $urlArray[3],
        ];
        $params['file'] = substr($url, strpos($url, $params['pattern'])+strlen($params['pattern'])+1);
        return $params;
    }


    public function process()
    {
        $params = $this->getParams();

        $source = $this->getConfig('sources', $params['server']);
        if (empty($source)) {
            throw new \Exception('this source is not known');
        }
        $pattern = $this->getConfig('patterns', $params['pattern']);
        if (empty($pattern)) {
            throw new Exception('this pattern is not known');
        }

        $cacheDir = $this->getConfig('cache');

        $fileIn = realpath($source . '/' . $params['file']);
        if ($fileIn===false) {
            throw new Exception('InFile not exists');
        }
        if (strpos($fileIn, $source)===false) {
            throw new Exception('InFile outer of allowed path');
        }
        $dirOut = dirname($cacheDir . '/' . $params['server'] . '/' . $params['pattern'] . '/' . $params['file']);
        if (!file_exists($dirOut)) {
            if (!mkdir($dirOut, 0775, true)) {
                throw new \RuntimeException('Can not create output dir');
            }
        }
        $fileOut = realpath($dirOut) . '/' . basename($params['file']);
        if (strpos($fileOut, $cacheDir)===false) {
            throw new Exception('OutFile outer of allowed path');
        }

        if (file_exists($fileOut) and (filesize($fileOut))>0) {
            return $fileOut;
        }

        $tmpFile = new TmpFile($this->getConfig('tmp'));

        $command = str_replace(['#INPUT#', '#OUTPUT#'], [escapeshellarg ($fileIn), $tmpFile], $pattern);

        exec($command, $output, $result);

        if (filesize($tmpFile)==0) {
            unlink($tmpFile);
            throw new Exception('Error in creating file');
        }

        rename($tmpFile, $fileOut);

        return $fileOut;
    }

    public function give()
    {
        $file = $this->process();
        $this->headerAccel($file);
    }
}