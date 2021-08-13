<?php

namespace Appwrite\Stats;

use Utopia\App;

class Stats
{
    /**
     * @var array
     */
    protected $params = [];

    /**
     * @var mixed
     */
    protected $statsd;

    /**
     * @var string
     */
    protected $namespace = 'appwrite.usage';

    /**
     * Event constructor.
     *
     * @param mixed $statsd
     */
    public function __construct($statsd)
    {
        $this->statsd = $statsd;
    }

    /**
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function setParam(string $key, $value): self
    {
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * @param string $key
     *
     * @return mixed|null
     */
    public function getParam(string $key)
    {
        return (isset($this->params[$key])) ? $this->params[$key] : null;
    }

    /**
     * @param string $namespace
     *
     * @return $this
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Submit data to StatsD.
     */
    public function submit(): void
    {
        $projectId = $this->params['projectId'] ?? '';

        $storage = $this->params['storage'] ?? 0;

        $networkRequestSize = $this->params['networkRequestSize'] ?? 0;
        $networkResponseSize = $this->params['networkResponseSize'] ?? 0;

        $httpMethod = $this->params['httpMethod'] ?? '';
        $httpPath = $this->params['httpPath'] ?? '';
        $httpRequest = $this->params['httpRequest'] ?? 0;

        $functionId = $this->params['functionId'] ?? '';
        $functionExecution = $this->params['functionExecution'] ?? 0;
        $functionExecutionTime = $this->params['functionExecutionTime'] ?? 0;
        $functionStatus = $this->params['functionStatus'] ?? '';

        $tags = ",project={$projectId},version=" . App::getEnv('_APP_VERSION', 'UNKNOWN');

        // the global namespace is prepended to every key (optional)
        $this->statsd->setNamespace($this->namespace);

        if ($httpRequest >= 1) {
            $this->statsd->increment('requests.all' . $tags . ',method=' . \strtolower($httpMethod).',path='.$httpPath);
        }

        if ($functionExecution >= 1) {
            $this->statsd->increment('executions.all' . $tags . ',functionId=' . $functionId . ',functionStatus=' . $functionStatus);
            $this->statsd->count('executions.time' . $tags . ',functionId=' . $functionId, $functionExecutionTime);
        }

        $this->statsd->count('network.inbound' . $tags, $networkRequestSize);
        $this->statsd->count('network.outbound' . $tags, $networkResponseSize);
        $this->statsd->count('network.all' . $tags, $networkRequestSize + $networkResponseSize);

        if ($storage >= 1) {
            $this->statsd->count('storage.all' . $tags, $storage);
        }

        $this->reset();
    }

    public function reset(): self
    {
        $this->params = [];
        $this->namespace = 'appwrite.usage';

        return $this;
    }
}