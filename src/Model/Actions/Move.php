<?php
    namespace App\Model\Actions;

    /**
     * добавить фиксирование перемещений 
     */
    class Move implements Interfaces\iMove
    {
        /**
         * @var App\Model\Warehouse
         */
        public $from;

        /**
         *  @var App\Model\Warehouse
         */
        public $to;
        
        /**
         * 'false' - moving failed
         * 'true' - moving done
         * @return bool
         */
        public function moveItem(int $id, int $quantity)
        {
            if ($this->from == null || $this->to == null){//?? подумай, ведь конструктор хочет что-то определенное
                fputs(STDOUT, "One or more warehouses are not exist.");
            } else {
                $helper = $this->from->removeItem($id, $quantity);
                if (!is_null($helper)){
                    $this->to->addItem($helper);
                    return true;
                }
            }
            return false;
        }

        public function __construct(\App\Model\Warehouse $from, \App\Model\Warehouse $to)
        {
            $this->from = $from;
            $this->to = $to;
        }
    }