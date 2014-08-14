<?php

namespace TinderFun\Command;

use Guzzle\Http\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Parser;

class BotCommand extends Command
{
    const TINDER_API_ENDPOINT = 'https://api.gotinder.com';
    const TINDER_USER_AGENT = 'Tinder/4.0.4 (iPhone; iOS 7.1; Scale/2.00)';

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var array
     */
    protected $config;

    /**
     * Configure
     */
    protected function configure()
    {
        $this
            ->setName('launch')
            ->setDescription('Tinder magics')
        ;
    }

    /**
     * Execute
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Reading config file
        $this->parseConfig();

        // Creating Guzzle client
        $this->initClient();
        $output->writeln("- Client created.");

        // Fetching Tinder access token
        $token = $this->authenticateClient();
        $output->writeln(sprintf("- Authentication successful. Access token: <info>%s</info>.", $token));

        // Update location from config file
        $location = $this->updateLocation();
        $output->writeln("- Updating geoloc.");
        if (isset($location['error'])) {
            $output->writeln('- ' . $location['error']);
        }

        // Fetching recommendations
        $recs = $this->getRecommendations();
        foreach ($recs as $user) {
            $output->writeln(sprintf("- Liking <info>%s</info>", $user['name']));

            // Liking the user
            $this->likeUser($user);

            sleep(2);
        }
    }

    /**
     * Parse config
     */
    protected function parseConfig()
    {
        $yaml = new Parser();
        try {
            $this->config = $yaml->parse(file_get_contents(__DIR__."/../../../config/config.yml"));
        } catch (\Exception $ex) {
            throw new \Exception("Configuration file could not be read, please make sure you copied the config template located in config/config.yml.dist");
        }
    }

    /**
     * Init client
     */
    protected function initClient()
    {
        $this->client = new Client(self::TINDER_API_ENDPOINT, [
            "request.options" => [
                "headers" => [
                    "os_version" => 700001,
                    "Accept-Language" => "fr;q=1, en;q=0.9, de;q=0.8, ja;q=0.7, nl;q=0.6, it;q=0.5",
                    "Accept-Encoding" => "gzip, deflate",
                    "Accept" => '*/*',
                    "Content-Type" => "application/json; charset=utf-8",
                    "Connection" => "keep-alive",
                    "platform" => 'ios',
                    "app-version" => 90
                ],
            ],
        ]);
        $this->client->setUserAgent(self::TINDER_USER_AGENT);
    }

    /**
     * Authenticate
     */
    protected function authenticateClient()
    {
        $request = $this->client->createRequest('POST', '/auth');
        $request->setBody(json_encode([
            'facebook_token' => $this->config['facebook']['token'],
            'facebook_id' => $this->config['facebook']['id'],
        ]));

        $response = $this->client->send($request);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Your facebook ID or your facebook token is invalid.");
        }

        $data = $response->json();

        if (!isset($data['token'])) {
            throw new \Exception("Could not fetch tinder access token.");
        }

        $this->client->setDefaultOption('headers/Authorization', sprintf('Token token="%s"', $data['token']));
        $this->client->setDefaultOption('headers/X-Auth-Token', $data['token']);

        return $data['token'];
    }

    /**
     * Authenticate
     */
    protected function updateLocation()
    {
        $request = $this->client->createRequest('POST', '/user/ping');
        $request->setBody(json_encode([
            'lat' => $this->config['location']['lat'],
            'lon' => $this->config['location']['lon'],
        ]));

        $response = $this->client->send($request);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Your location couldn't be updated.");
        }

        return $response->json();
    }

    /**
     * Recommendation
     */
    protected function getRecommendations()
    {
        $request = $this->client->createRequest('GET', '/user/recs');

        $response = $this->client->send($request);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception("Could not fetch recommendations.");
        }

        $data = $response->json();

        return $data['results'];
    }

    /**
     * Like User
     *
     * @param $user
     * @throws \Exception
     */
    protected function likeUser($user)
    {
        $request = $this->client->createRequest('GET', '/like/' . $user['_id']);

        $response = $this->client->send($request);

        if ($response->getStatusCode() !== 200) {
            throw new \Exception(sprintf("Could not like %s.\nStatus: %s\nError: %s", $user['name'], $response->getStatusCode(), $response->getBody()));
        }
    }
}
