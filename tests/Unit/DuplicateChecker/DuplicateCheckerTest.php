<?php
// write a test that checks if the DuplicateCheckerController exists
// write a test that checks if the DuplicateCheckerController has a method called index
// write a test that checks if the DuplicateCheckerController has a method called show
// write a test that checks if the DuplicateCheckerController has a method called update
// write a test that checks if the DuplicateCheckerController has a method called destroy
// write a test that checks if the DuplicateCheckerController has a method called store
// write a test that checks if the DuplicateCheckerController has a middleware called client
// write a test that checks if the DuplicateCheckerController has a route that points to the index method
// write a test that checks if the DuplicateCheckerController has a route that points to the show method
// write a test that checks if the DuplicateCheckerController has a route that points to the update method
// write a test that checks if the DuplicateCheckerController has a route that points to the destroy method
// write to make post to endpoint /duplicates

use App\Http\Controllers\DuplicateChecker\DuplicateCheckerController;
use Illuminate\Routing\Route;
use Tests\TestCase;

uses(TestCase::class);
// write a test that checks if the DuplicateCheckerController exists
it('checks if the DuplicateCheckerController exists', function () {
    $this->assertTrue(class_exists(DuplicateCheckerController::class));
});
// write a test that checks if the DuplicateCheckerController has a method called index
it('checks if the DuplicateCheckerController has a method called index', function () {
    $this->assertTrue(method_exists(DuplicateCheckerController::class, 'index'));
});
// write a test that checks if the DuplicateCheckerController has a method called show
it('checks if the DuplicateCheckerController has a method called show', function () {
    $this->assertTrue(method_exists(DuplicateCheckerController::class, 'show'));
});
// write a test that checks if the DuplicateCheckerController has a method called update
it('checks if the DuplicateCheckerController has a method called update', function () {
    $this->assertTrue(method_exists(DuplicateCheckerController::class, 'update'));
});
// write a test that checks if the DuplicateCheckerController has a method called destroy
it('checks if the DuplicateCheckerController has a method called destroy', function () {
    $this->assertTrue(method_exists(DuplicateCheckerController::class, 'destroy'));
});
// write a test that checks if the DuplicateCheckerController has a method called store
it('checks if the DuplicateCheckerController has a method called store', function () {
    $this->assertTrue(method_exists(DuplicateCheckerController::class, 'store'));
});

// write a test that checks if the DuplicateCheckerController has a middleware called client
it('checks if the DuplicateCheckerController has a middleware called client', function () {
    $controller = new DuplicateCheckerController();
    $this->assertContains('client', $controller->getMiddleware());
});


// write a test that checks if the DuplicateCheckerController has a route that points to the index method
it('checks if the DuplicateCheckerController has a route that points to the index method', function () {
    $response = $this->get(route('users.index'));
    $response->assertStatus(200);
    $routes = app('router')->getRoutes();
    $route = $routes->getRoutes()[0];
    $this->assertEquals('GET', $route->methods[0]);
    $this->assertEquals('/duplicates', $route->uri);
    $this->assertEquals('DuplicateCheckerController@index', $route->action['controller']);
});

// write a test that checks if the DuplicateCheckerController has a route that points to the show method
it('checks if the DuplicateCheckerController has a route that points to the show method', function () {
    $routes = app('router')->getRoutes();
    $route = $routes->getRoutes()[1];
    $this->assertEquals('GET', $route->methods[0]);
    $this->assertEquals('/duplicates/{id}', $route->uri);
    $this->assertEquals('DuplicateCheckerController@show', $route->action['controller']);
});

// write a test that checks if the DuplicateCheckerController has a route that points to the update method
it('checks if the DuplicateCheckerController has a route that points to the update method', function () {
    $routes = app('router')->getRoutes();
    $route = $routes->getRoutes()[2];
    $this->assertEquals('PUT', $route->methods[0]);
    $this->assertEquals('/duplicates/{id}', $route->uri);
    $this->assertEquals('DuplicateCheckerController@update', $route->action['controller']);
});

// write a test that checks if the DuplicateCheckerController has a route that points to the destroy method

it('checks if the DuplicateCheckerController has a route that points to the destroy method', function () {
    $routes = app('router')->getRoutes();
    $route = $routes->getRoutes()[3];
    //$this->assertEquals('DELETE', $route->methods[0]);
    $this->assertEquals('/duplicates/{id}', $route->uri);
    $this->assertEquals('DuplicateCheckerController@destroy', $route->action['controller']);
});

// write to make post to endpoint /duplicates
it('checks if the DuplicateCheckerController has a route that points to the store method', function () {
    $routes = app('router')->getRoutes();
    $route = $routes->getRoutes()[0];
    $this->assertEquals('POST', $route->methods[0]);
    $this->assertEquals('/duplicates', $route->uri);
    $this->assertEquals('DuplicateCheckerController@store', $route->action['controller']);
});