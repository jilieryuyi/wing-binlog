<?php namespace Seals\Library;
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/3/21
 * Time: 07:26
 */
class Config
{
    private $raw;
    public function __construct($data)
    {
        $this->raw = $data;
    }

    public function __get($name)
    {
        if (is_array($this->raw)) {
            if(isset($this->raw[$name])) {
                if (is_array($this->raw[$name]))
                    return new self($this->raw[$name]);
                else
                    return $this->raw[$name];
            } else {
                return null;
            }
        } elseif (is_object($this->raw)) {
            $class = new \ReflectionClass($this->raw);
            if ($class->hasProperty($name))
                $data = $class->getProperty($name);
            else
                $data = null;
            unset($class);
            return $data;
        } else {
            return $this->raw;
        }
    }

    public function get($name)
    {
        return $this->__get($name);
    }

    public function __destruct()
    {
        unset($this->raw);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        if (is_array($this->raw))
            return $this->raw;

        elseif (is_object($this->raw)) {
            $res         = [];
            $class       = new \ReflectionClass($this->raw);
            $properties  = $class->getProperties();

            foreach ($properties as $property) {
                $key   = $property->getName();
                $value = $property->getValue();
                $res[$key] = $value;
            }

            return $res;
        } else {
            return [$this->raw];
        }
    }

    public function write($file)
    {
        $config = "<?php \r\nreturn [\r\n";

        $temp = [];
        $arr  = $this->toArray();

        foreach ($arr as $key => $value) {
            $value     = urldecode($value);

            if ($key == "password") {
                if ($value == ":null")
                    $value = "null";
                elseif ($value == ":empty")
                    $value = "\"\"";
                else
                    $value = "\"".$value."\"";
            } else {
                $value = "\"".$value."\"";
            }
            $temp[] = "\"".$key."\" => ".$value;
        }

        $config .= implode(",\r\n", $temp);
        $config .="\r\n];";

        return file_put_contents($file, $config);
    }
}