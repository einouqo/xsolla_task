<?php
    namespace App\Services;

    use App\Repository\CompanyRepository;

    class CompanyService
    {
        /**
         * @var CompanyRepository
         */
        private $companyRepository;

        public function __construct(CompanyRepository $companyRepository)
        {
            $this->companyRepository = $companyRepository;
        }

        /**
         * @return array
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getList()
        {
            return $this->companyRepository->getList();
        }

        /**
         * @param string $name
         * @return string
         * @throws \Doctrine\DBAL\DBALException
         */
        public function create(string $name)
        {
            return $this->companyRepository->create($name);
        }

        /**
         * @param array $data
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         * @return string
         */
        public function delete(array $data)
        {
            if (is_null($data['companyID']) || $data['companyID'] == '') {
                throw new \Exception('Company ID cannot be empty.', 403);
            }
            if (!is_numeric($data['companyID'])) {
                throw new \Exception('Company ID may consist digits only.', 403);
            }
            if (!$this->companyRepository->isEmptyWarehouses($data['companyID'])) {
                throw new \Exception('Some warehouses have items. Delete is prohibited.', 403);
            }
            if (!$this->companyRepository->isPendingTransfers($data['companyID'])) {
                throw new \Exception('Some transfers are not accepted. Delete is prohibited.', 403);
            }

            return $this->companyRepository->delete($data);
        }
    }