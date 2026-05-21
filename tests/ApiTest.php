<?php

declare(strict_types=1);

use G4\Api\App\Api;
use G4\Api\Model\Task;
use G4\Api\Model\TaskStatus;
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    private static function getValidToken(): string
    {
        // @mago-ignore lint:no-shorthand-ternary
        return 'Bearer ' . (getenv('API_TEST_TOKEN') ?: 'g4-token-2024');
    }

    protected function setUp(): void
    {
        $_SERVER = array_filter($_SERVER, static fn(string $key): bool => !str_starts_with($key, 'HTTP_'), ARRAY_FILTER_USE_KEY);
        $_SERVER['REQUEST_METHOD'] = '';
        $_GET = [];
        $_POST = [];
    }

    public function testAuthSuccess(): void
    {
        $_GET['request'] = '/auth';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['name'] = 'G4';
        // @mago-ignore lint:no-shorthand-ternary
        $_POST['api_token'] = getenv('API_TEST_TOKEN') ?: 'g4-token-2024';

        $api = new Api();
        $response = $api->processRequest()->getFormatedResponseForContent();

        $this->assertIsString($response);
        $decode = json_decode($response, true);
        $this->assertIsArray($decode);
        $this->assertEquals('success', $decode['status']);
        $this->assertArrayHasKey('data', $decode);
        $this->assertEquals(1, $decode['data']['user_id']);
    }

    public function testAuthFailure(): void
    {
        $_GET['request'] = '/auth';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['name'] = 'G4';
        // @mago-ignore lint:no-literal-password
        $_POST['api_token'] = 'invalid-token-for-test';

        $api = new Api();
        $response = $api->processRequest()->getFormatedResponseForContent();

        $this->assertIsString($response);
        $decode = json_decode($response, true);
        $this->assertIsArray($decode);
        $this->assertEquals('error', $decode['status']);
        $this->assertEquals(401, $api->getCode());
    }

    public function testUnauthorizedWrite(): void
    {
        $_GET['request'] = '/task/';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $api = new Api();
        $response = $api->processRequest()->getFormatedResponseForContent();

        $this->assertIsString($response);
        $decode = json_decode($response, true);
        $this->assertEquals(401, $api->getCode());
    }

    public function testGetUser(): void
    {
        $_GET['request'] = '/user/1';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $api = new Api();
        $response = $api->processRequest()->getFormatedResponseForContent();

        $this->assertIsString($response);
        $decode = json_decode($response, true);
        $this->assertIsArray($decode);
        $this->assertEquals('success', $decode['status']);
        $this->assertArrayHasKey('data', $decode);
        $this->assertEquals(1, $decode['data']['user_id']);
    }

    public function testGetUserTask(): void
    {
        $_GET['request'] = '/user/1/task';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $api = new Api();
        $response = $api->processRequest()->getFormatedResponseForContent();

        $this->assertIsString($response);
        $decode = json_decode($response, true);
        $this->assertIsArray($decode);
        $this->assertEquals('success', $decode['status']);
        $this->assertArrayHasKey('data', $decode);
        $this->assertEquals(1, $decode['data']['user_id']);
        $this->assertArrayHasKey('tasks', $decode['data']);
    }

    public function testAddTask(): void
    {
        $_GET['request'] = '/task/';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_AUTHORIZATION'] = self::getValidToken();
        $_POST['status'] = TaskStatus::Backlog->value;
        $_POST['title'] = 'Faire le thè';
        $_POST['description'] = 'Comme pour le café, mais avec du thé';

        $api = new Api();
        $response = $api->processRequest()->getFormatedResponseForContent();

        $this->assertIsString($response);
        $decode = json_decode($response, true);
        $this->assertIsArray($decode);
        $this->assertEquals('success', $decode['status']);
        $this->assertArrayHasKey('data', $decode);
        $this->assertArrayHasKey('task_id', $decode['data']);
        $this->assertArrayHasKey('title', $decode['data']);
        $this->assertEquals(201, $api->getCode());
    }

    public function testEditTask(): void
    {
        $task = new Task();
        $taskList = $task->getAll();
        $last = array_pop($taskList);

        $_GET['request'] = '/task/' . $last['task_id'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_AUTHORIZATION'] = self::getValidToken();
        $_POST['status'] = TaskStatus::Backlog->value;
        $_POST['title'] = 'Faire le thè';
        $_POST['description'] = 'Comme pour le café, mais avec du thé et en mieux';

        $api = new Api();
        $response = $api->processRequest()->getFormatedResponseForContent();

        $this->assertIsString($response);
        $decode = json_decode($response, true);
        $this->assertEquals('success', $decode['status']);
        $this->assertEquals(1, $decode['data']);
    }

    public function testAddTaskToUser(): void
    {
        $task = new Task();
        $taskList = $task->getAll();
        $last = array_pop($taskList);

        $_GET['request'] = '/user/1/task/' . $last['task_id'];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_AUTHORIZATION'] = self::getValidToken();

        $api = new Api();
        $response = $api->processRequest()->getFormatedResponseForContent();

        $this->assertIsString($response);
        $decode = json_decode($response, true);
        $this->assertEquals('success', $decode['status']);
        $this->assertEquals(1, $decode['data']);
    }

    public function testDelTaskToUser(): void
    {
        $task = new Task();
        $taskList = $task->getAll();
        $last = array_pop($taskList);

        $_GET['request'] = '/user/1/task/' . $last['task_id'];
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['HTTP_AUTHORIZATION'] = self::getValidToken();

        $api = new Api();
        $response = $api->processRequest()->getFormatedResponseForContent();

        $this->assertIsString($response);
        $decode = json_decode($response, true);
        $this->assertEquals('success', $decode['status']);
        $this->assertEquals(1, $decode['data']);
    }

    public function testDelTask(): void
    {
        $task = new Task();
        $taskList = $task->getAll();
        $last = array_pop($taskList);

        $_GET['request'] = '/task/' . $last['task_id'];
        $_SERVER['REQUEST_METHOD'] = 'DELETE';
        $_SERVER['HTTP_AUTHORIZATION'] = self::getValidToken();

        $api = new Api();
        $response = $api->processRequest()->getFormatedResponseForContent();

        $this->assertIsString($response);
        $decode = json_decode($response, true);
        $this->assertEquals('success', $decode['status']);
        $this->assertEquals(1, $decode['data']);
    }
}
