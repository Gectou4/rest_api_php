<?php

declare(strict_types=1);

use G4\Api\App\Api;
use G4\Api\Model\Task;
use G4\Api\Model\TaskStatus;
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        // Initialisation DB si nécessaire
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

        $api = new Api();
        $response = $api->processRequest()->getFormatedResponseForContent();

        $this->assertIsString($response);
        $decode = json_decode($response, true);
        $this->assertEquals('success', $decode['status']);
        $this->assertEquals(1, $decode['data']);
    }
}
