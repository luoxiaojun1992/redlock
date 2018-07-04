<?php

use Mockery as M;

class RedLockTest extends \PHPUnit\Framework\TestCase
{
    private $pipeline;
    private $redis;

    const PREFIX = 'lock:';
    const KEY = 'test_lock';
    const TTL = 5;

    public function setUp()
    {
        parent::setUp();

        $this->mock();
    }

    private function mock()
    {
        //Mock Predis Pipeline
        $pipeline = M::mock(\Predis\Pipeline\Pipeline::class);
        $pipeline->shouldReceive('setnx')
            ->with(self::PREFIX . self::KEY, 1)
            ->andReturn('QUEUED');
        $pipeline->shouldReceive('expire')
            ->with(self::PREFIX . self::KEY, self::TTL)
            ->andReturn('QUEUED');
        $pipeline->shouldReceive('execute')
            ->andReturn([1, 1]);
        $this->pipeline = $pipeline;

        //Mock Predis Client
        $redis = M::mock(\Predis\Client::class);
        $redis->shouldReceive('pipeline')
            ->andReturn($pipeline);
        $redis->shouldReceive('del')
            ->with(self::PREFIX . self::KEY)
            ->andReturn(1);
        $this->redis = $redis;
    }

    public function testLock()
    {
        $redLock = new Lxj\RedLock\RedLock($this->redis, self::PREFIX);
        $this->assertTrue($redLock->lock(self::KEY, self::TTL));
    }

    public function testUnLock()
    {
        $redLock = new Lxj\RedLock\RedLock($this->redis, self::PREFIX);
        $redLock->lock(self::KEY, self::TTL);
        $this->assertTrue($redLock->unlock(self::KEY));
    }

    public function testFlushAll()
    {
        //Mock Predis Client
        $redis = M::mock(\Predis\Client::class);
        $redis->shouldReceive('pipeline')
            ->andReturn($this->pipeline);
        $redis->shouldReceive('del')
            ->with(self::PREFIX . self::KEY)
            ->once()
            ->andReturn(1);
        $redis->shouldReceive('del')
            ->with(self::PREFIX . self::KEY)
            ->once()
            ->andReturn(0);

        $redLock = new Lxj\RedLock\RedLock($redis, self::PREFIX);
        $redLock->lock(self::KEY, self::TTL);
        $redLock->flushAll();

        $this->assertFalse($redLock->unlock(self::KEY));
    }

    public function tearDown()
    {
        parent::tearDown();
    }
}
