<?php
    namespace App\Model\Actions\Interfaces;

    interface iMove
    {
        public function moveItem(int $id, int $quantity);
    }