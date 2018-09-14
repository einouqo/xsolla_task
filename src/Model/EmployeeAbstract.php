<?php
    namespace App\Model;

    abstract class EmployeeAbstract
    {
        /**
         * @var int
         */
        protected $id;

        /**
         * @var string
         */
        protected $name;

        /**
         * @var string
         */
        protected $lastname;

        /**
         * @var int
         */
        protected $companyID;

        /**
         * @var string
         */
        protected $email;

        /**
         * @var int
         */
        protected $phone;

        /**
         * @var string
         */
        protected $password;

        protected function __construct(array $data)
        {
            $this->id = $data['id'];
            $this->name = $data['name'];
            $this->lastname = $data['lastname'];
            $this->companyID = $data['companyID'];
            $this->email = $data['email'];
            $this->password = $data['password'];
            $this->phone = $data['phone'];
        }

        public function getCompanyID()
        {
            return $this->companyID;
        }

        public function getName()
        {
            return $this->name;
        }

        public function getLastname()
        {
            return $this->lastname;
        }

        public function getEmail()
        {
            return $this->email;
        }

        public function getPhone()
        {
            return $this->phone;
        }

        public function getPassword()
        {
            return $this->password;
        }

        public function getID()
        {
            return $this->id;
        }

        public function setID(int $id)
        {
            $this->id = $id;
        }

        /**
         * @param array $newData
         * @return null|string
         */
        public function isChangeable(array $newData)
        {
            foreach ($newData as $field => $data) {
                if ($this->$field == $data) {
                    return $field;
                }
            }
            return null;
        }
    }