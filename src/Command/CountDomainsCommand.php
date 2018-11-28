<?php
//
namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Console\Input\InputArgument;

class CountDomainsCommand extends Command
{
    /**
     * Connection to db
     */
    private $connection;

    /**
     * Collected domains with domain as key and value as count of users
     */
    private $domains = [];

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        parent::__construct();
    }

    protected function configure()
    {
      $this->setName('count-domains')
        ->setDescription('Counts users by email domain.')
        ->addArgument('batch', InputArgument::OPTIONAL, 'Batch size per iteration.', 1000);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $batch = $input->getArgument('batch');
        $this->iterate($batch, 0);
        arsort($this->domains);
        foreach ($this->domains as $domain => $count) {
          $output->writeln($count . ' ' . $domain);
        }
    }

    private function iterate(int $batch, int $lastID): void
    {
        $users = $this->getUsers($batch, $lastID);

        if (count($users) > 0) {
          foreach ($users as $user) {
            $this->collectDomains($this->getUserEmails($user['email']));
          }
          $lastID = max(array_column($users, 'id'));
          $this->iterate($batch, $lastID);
        }
    }

    private function getUserEmails(string $emailString): array
    {
        $emails = \explode(',', $emailString);
        return array_filter($emails, function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
    }

    private function collectDomains(array $emails): void
    {
        foreach ($emails as $email) {
            $domain = substr(strrchr($email, "@"), 1);
            if (!isset($this->domains[$domain])) {
              $this->domains[$domain] = 0;
            }
            $this->domains[$domain] = $this->domains[$domain] + 1;
        }
    }

    private function getUsers(int $batch, int $lastID): array
    {
      $stmt = $this->connection->prepare("SELECT id, email
        FROM users
        WHERE id > :lastID
        ORDER BY id ASC
        LIMIT :batch");

      $stmt->bindValue("lastID", $lastID, ParameterType::INTEGER);
      $stmt->bindParam("batch", $batch, ParameterType::INTEGER);
      $stmt->execute();

      return $stmt->fetchAll();
    }
}
