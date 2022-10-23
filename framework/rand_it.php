<?
/* случайная выборка */
namespace framework;

class rand_it
{
    private $_array = array(); // массив для выборки
    private $_count_val = 0; // количество выбираемых элементов
    private $_name_value = ''; // поле для возврата
    private $_name_weight = ''; // поле веса
    private $_only_weight = false; // выбирать ли по алгоритму "высших весов"
    private $_difference = 0; // дополнительные элементы (двумерный массив)
    private $_fill = true; //заполнять ли все элементы или вернуть только ограниченное количество
    private $_feed = 0; //задает фид генератора
    
    public function setArray($array)
    {
       $this->_array = (array) $array; 
    }
    
    public function setCountVal($count_val)
    {
        $this->_count_val = (int) $count_val;
    }    
    
    public function setNameValue($name_value)
    {
       $this->_name_value = (string) $name_value; 
    }
    
    public function setNameWeight($name_weight)
    {
       $this->_name_weight = (string) $name_weight; 
    }
    
    public function setOnlyWeight($only_weight)
    {
       $this->_only_weight  = (bool) $only_weight; 
    }
    
    public function setDifference($difference)
    {
        $this->_difference = (int) $difference;
    }
    
    public function setFill($fill)
    {
       $this->_fill = (bool) $fill; 
    }
    
    public function setFeed($feed)
    {
       $this->_feed = (int) $feed; 
    }
    
    public static function randMas($array, $count_val, $name_value = '', $feed = 0)
    {
        if (!$array) return array();
        
        if (!$feed) 
            srand();
        else
            srand($feed);        
        
        $rand_array = array();
        
        for ($i=0; $i < $count_val; $i++)
        {
            $count = count($array);
            
            if ($count == 1)
            {
                if ($name_value)
                    $rand_array[] = $array[0][$name_value];  
                else
                    $rand_array[] = $array[0];
            }
            else
            {
                $rand = rand(0, $count-1);
                
                if ($name_value)
                    $rand_array[] = $array[$rand][$name_value];
                else
                    $rand_array[] = $array[$rand];
                    
                unset($array[$rand]);
                $array = array_values($array);
            }       
        }
        
        return $rand_array; 
    }
    
    public function randIt()
    {
        $array = $this->_array;
        $count_val = $this->_count_val;
        $name_value = $this->_name_value;
        $name_weight = $this->_name_weight;
        $only_weight = $this->_only_weight;
        $difference = $this->_difference;
        $fill = $this->_fill;
        $feed = $this->_feed;
        
        if (!$array || !$count_val || !$name_value)
            return false;        
                
        if (!$feed) 
            srand();
        else
            srand($feed);
                 
        $rand_array = array();        
        $count = count($array);
        
        // выборка высших весов
        if ($only_weight && $name_weight)
        {
            $count_weight = 0;
            foreach ($array as $key => $value)
            {
                if ( ((integer) $value[$name_weight]) > 1)
                {
                    $count_weight++;
                    $array[$key]['double'] = true;
                }
                else
                    $array[$key]['double'] = false;                                                    
            }
            
            // сбрасываем учет весов
            $name_weight = '';
            
            // если высших больше 1-го, то распределяем среди них
            if ($count_weight > 1)
            {
                foreach ($array as $key => $value)
                {
                    if (!$value['double']) unset($array[$key]);
                }
                
                $array = array_values($array);                
            }
            else
            {
                 // если 1 - то он должен встречаться не менее двух раз, 
                 // в противном случае работаем по обычному алгоритму 
                 if ($count_weight == 1 && $count_val > 1)
                 {
                    //за исключение количества равным одному (чтобы не выбирать его каждый раз)
                    $t_array = array();
                    foreach ($array as $key => $value)
                    {
                        if ($value['double']) 
                        {
                            $d = $value;
                            unset($array[$key]);
                            break;
                        }
                    }
                    
                    $array = array_values($array);                    
            
                    $half = ceil($count_val / 2);
                    
                    for ($i=0; $i < $half; $i++)
                        $t_array[] = $d;
                                        
                    $t_array = array_merge($t_array, rand_it::randMas($array, $count_val - $half, '', $feed));
                    
                    $array = $t_array;
                 }                  
            }                   
        }
        
        $count = count($array);
        
        if ($count >= $count_val)
        {
            if ($count == $count_val || ($count > $count_val && !$name_weight))
            {
                $rand_array = rand_it::randMas($array, $count_val, $name_value, $feed);
            }
            else
            {
                $t_array = array();
                foreach ($array as $value)
                    $t_array[$value[$name_value]] = $value; 
     
                // удваиваем элементы по их весам, проводим выборку среди них
                for ($i=0; $i < $count_val; $i++)
                {
                    $s_array = array();
                
                    foreach ($t_array as $value)
                    {
                        $s_array[] = $value;
                        $doubles = (integer) $value[$name_weight];
                        if ($doubles > 1)
                        {
                            for ($j=0; $j<($doubles-1); $j++)
                                $s_array[] = $value;
                        }
                    }
                    
                    $rand = rand(0, count($s_array)-1);
                    $r = $s_array[$rand][$name_value];
                    $rand_array[] = $r;
                                
                    unset($t_array[$r]);
                }
            }
        }
        else
        {
            if ($count == 1)
            {
                if (!$fill) $count_val = 1; 
                
                for ($i=0; $i < $count_val; $i++)
                    $rand_array[] = $array[0][$name_value]; 
                
            }
            else
            {
                $t_array = array();
                if ($fill)
                {
                    $j = 0;
                    
                    //делаем так, чтобы каждый элемент по возможности встречался не чаще другого
                    for ($i=0; $i < $count_val; $i++)
                    {
                        if ($j > ($count-1)) $j = 0;
                        $t_array[] = $array[$j]; 
                        $j++;
                    }
                    
                    $t_count_val = $count_val;
                }
                else
                {
                    $t_array = $array;
                    $t_count_val = $count;
                }
                                    
                $rand_array = rand_it::randMas($t_array, $t_count_val, $name_value, $feed);  
            }    
        }
        
        //возвращаем массив
        if ($difference)
        {
            for ($i=0; $i<$count_val; $i++)
            {
                $array = $this->_array;
                foreach ($array as $key => $value)
                {
                    if ($value[$name_value] == $rand_array[$i])
                    {
                        unset($array[$key]);
                        break;
                    }
                }
                
                $array = array_values($array);
                
                $t_a = array();
                if (count($array) > 0)
                {
                    $rnd = new rand_it();
                    $rnd->setArray($array);
                    $rnd->setCountVal($difference - 1);
                    $rnd->setNameValue($name_value);
                    $t_a = $rnd->randIt();
                }
                else
                {
                    for ($j=0; $j<$difference - 1; $j++)
                        $t_a[] = $rand_array[$i];
                }                
                
                $rand_array[$i] = array_merge((array) $rand_array[$i], $t_a);
            }
        }
        
        return $rand_array;
    }
}

?>