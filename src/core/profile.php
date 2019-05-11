<?php

    class Person{

      var  $name;

      public function __construct($persons_name){
          $this->name =$persons_name;
      }

        public function set_name($new_name, $bio){
            $this->name =$new_name;
            $this->bio = $bio;
        }

        public function get_name(){
            return $this->name;
        }
    }

?>