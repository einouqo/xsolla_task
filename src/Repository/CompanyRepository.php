<?php
    namespace App\Repository;

    use Doctrine\DBAL\Connection;

    class CompanyRepository
    {
        /**
         * @var Connection
         */
        private $dbConnection;

        public function __construct(Connection $dbConnection)
        {
            $this->dbConnection = $dbConnection;
        }

        /**
         * @return array
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getList()
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT id, name FROM company'
            );

            $company = [];
            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $company[] = [
                    'id' => $row['id'],
                    'name' => $row['name']
                ];
            }
            return $company;
        }

        /**
         * @param string $name
         * @return string
         * @throws \Doctrine\DBAL\DBALException
         */
        public function create(string $name)
        {
            $key = md5($name.(require __DIR__.'/../settings.php')['jwt']['secret']);

            $this->dbConnection->executeQuery(
                'INSERT INTO company(name, access_key) VALUES (?, ?)',
                [
                    $name,
                    password_hash($key, PASSWORD_DEFAULT)
                ]
            );
            return 'This is your private key for deleting company data. Do not lose it: '.$key;
        }

        private function deletePersonalInfo(array $personalInfo)
        {
            foreach ($personalInfo as $info) {
                if ($this->dbConnection->fetchAssoc(
                    'SELECT COUNT(*) AS count FROM users WHERE id_personalData = ?',
                    [
                        $info['id']
                    ]
                )['count'] == 0) {
                    $this->dbConnection->executeQuery(
                        'DELETE FROM personalInfo WHERE id = ?',
                        [
                            $info['id']
                        ]
                    );
                }
            }
        }

        /**
         * @param array $data
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         * @return string
         */
        public function delete(array $data)
        {
            $accessKey = $this->dbConnection->fetchAssoc(
                'SELECT access_key FROM company WHERE id = ?',
                [
                    $data['companyID']
                ]
            )['access_key'];

            if (is_null($accessKey)) {
                throw new \Exception('Company not found.', 404);
            }

            if (!password_verify($data['key'], $accessKey)) {
                throw new \Exception('Your key is incorrect. Access to delete action is prohibited.', 403);
            }

            $personalInfo = $this->dbConnection->fetchAssoc(
                'SELECT personalInfo.id AS id FROM personalInfo
                  INNER JOIN users on personalInfo.id = users.id_personalData AND id_company = ?',
                [
                    $data['companyID']
                ]
            );

            $this->dbConnection->executeQuery(
                'DELETE FROM company WHERE id = ?',
                [
                    $data['companyID']
                ]
            );

            $this->deletePersonalInfo($personalInfo);

            return 'Company was deleted successfully.';
        }
    }