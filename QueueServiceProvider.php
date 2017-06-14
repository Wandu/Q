<?php
namespace Wandu\Q;

use Aws\Sqs\SqsClient;
use Pheanstalk\PheanstalkInterface;
use Wandu\Config\Contracts\Config;
use Wandu\DI\ContainerInterface;
use Wandu\DI\ServiceProviderInterface;
use Wandu\Q\Adapter\BeanstalkdAdapter;
use Wandu\Q\Adapter\NullAdapter;
use Wandu\Q\Adapter\SqsAdapter;
use Wandu\Q\Contracts\Adapter;
use Wandu\Q\Contracts\Serializer;
use Wandu\Q\Serializer\JsonSerializer;
use Wandu\Q\Serializer\PhpSerializer;

class QueueServiceProvider implements ServiceProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(ContainerInterface $app)
    {
        $app->closure(Serializer::class, function (Config $config) {
            switch ($config->get('q.serializer')) {
                case 'json':
                    return new JsonSerializer();
            }
            return new PhpSerializer();
        });
        $app->closure(Adapter::class, function (ContainerInterface $app, Config $config) {
            switch ($config->get('q.type')) {
                case 'beanstalkd':
                    $app->assert(PheanstalkInterface::class, 'pda/pheanstalk');
                    return new BeanstalkdAdapter($app->get(PheanstalkInterface::class));
                case 'sqs':
                    $app->assert(SqsClient::class, 'aws/aws-sdk-php');
                    return new SqsAdapter($app->get(SqsClient::class), $config->get('q.sqs.url'));
            }
            return new NullAdapter();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function boot(ContainerInterface $app)
    {
    }
}
