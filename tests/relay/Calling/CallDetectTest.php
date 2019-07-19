<?php
require_once dirname(__FILE__) . '/BaseActionCase.php';

use SignalWire\Messages\Execute;

class RelayCallingCallDetectTest extends RelayCallingBaseActionCase
{
  protected static $notificationFaxCED;
  protected static $notificationFaxError;
  protected static $notificationFaxFinished;
  protected static $notificationMachineHuman;
  protected static $notificationMachineError;
  protected static $notificationMachineFinished;
  protected static $notificationDigitDTMF;
  protected static $notificationDigitError;
  protected static $notificationDigitFinished;

  public static function setUpBeforeClass() {
    self::$notificationFaxCED = json_decode('{"event_type":"calling.call.detect","params":{"control_id":"'.self::UUID.'","call_id":"call-id","node_id":"node-id","detect":{"type":"fax","params":{"event":"CED"}}}}');
    self::$notificationFaxError = json_decode('{"event_type":"calling.call.detect","params":{"control_id":"'.self::UUID.'","call_id":"call-id","node_id":"node-id","detect":{"type":"fax","params":{"event":"error"}}}}');
    self::$notificationFaxFinished = json_decode('{"event_type":"calling.call.detect","params":{"control_id":"'.self::UUID.'","call_id":"call-id","node_id":"node-id","detect":{"type":"fax","params":{"event":"finished"}}}}');

    self::$notificationMachineHuman = json_decode('{"event_type":"calling.call.detect","params":{"control_id":"'.self::UUID.'","call_id":"call-id","node_id":"node-id","detect":{"type":"machine","params":{"event":"HUMAN"}}}}');
    self::$notificationMachineError = json_decode('{"event_type":"calling.call.detect","params":{"control_id":"'.self::UUID.'","call_id":"call-id","node_id":"node-id","detect":{"type":"machine","params":{"event":"error"}}}}');
    self::$notificationMachineFinished = json_decode('{"event_type":"calling.call.detect","params":{"control_id":"'.self::UUID.'","call_id":"call-id","node_id":"node-id","detect":{"type":"machine","params":{"event":"finished"}}}}');

    self::$notificationDigitDTMF = json_decode('{"event_type":"calling.call.detect","params":{"control_id":"'.self::UUID.'","call_id":"call-id","node_id":"node-id","detect":{"type":"digit","params":{"event":"1#"}}}}');
    self::$notificationDigitError = json_decode('{"event_type":"calling.call.detect","params":{"control_id":"'.self::UUID.'","call_id":"call-id","node_id":"node-id","detect":{"type":"digit","params":{"event":"error"}}}}');
    self::$notificationDigitFinished = json_decode('{"event_type":"calling.call.detect","params":{"control_id":"'.self::UUID.'","call_id":"call-id","node_id":"node-id","detect":{"type":"digit","params":{"event":"finished"}}}}');
  }

  protected function setUp() {
    parent::setUp();

    $this->_setCallReady();
  }

  public function testDetectSuccess(): void {
    $msg = $this->_detectMsg('fax');
    $this->_mockSuccessResponse($msg);
    $this->call->detect('fax', [], 25)->done(function($result) {
      $this->assertInstanceOf('SignalWire\Relay\Calling\Results\DetectResult', $result);
      $this->assertTrue($result->isSuccessful());
      $this->assertEquals($result->getType(), 'fax');
      $this->assertEquals($result->getResult(), 'CED');
      $this->assertObjectHasAttribute('type', $result->getEvent()->payload);
      $this->assertObjectHasAttribute('params', $result->getEvent()->payload);
    });
    $this->calling->notificationHandler(self::$notificationFaxCED);
    $this->calling->notificationHandler(self::$notificationFaxFinished);
  }

  public function testDetectFail(): void {
    $msg = $this->_detectMsg('fax');
    $this->_mockFailResponse($msg);
    $this->call->detect('fax', [], 25)->done([$this, '__detectFailCheck']);
  }

  public function testDetectAsyncSuccess(): void {
    $msg = $this->_detectMsg('fax',['tone' => 'CED'], 45);
    $this->_mockSuccessResponse($msg);
    $this->call->detectAsync('fax', ['tone' => 'CED'], 45)->done(function($action) {
      $this->assertInstanceOf('SignalWire\Relay\Calling\Actions\DetectAction', $action);
      $this->assertInstanceOf('SignalWire\Relay\Calling\Results\DetectResult', $action->getResult());
      $this->assertFalse($action->isCompleted());
      $this->calling->notificationHandler(self::$notificationFaxFinished);
      $this->assertTrue($action->isCompleted());
    });
  }

  public function testDetectAsyncFail(): void {
    $msg = $this->_detectMsg('fax',['tone' => 'CED'], 45);
    $this->_mockFailResponse($msg);
    $this->call->detectAsync('fax', ['tone' => 'CED'], 45)->done([$this, '__detectAsyncFailCheck']);
  }

  public function testDetectHumanSuccess(): void {
    $msg = $this->_detectMsg('machine');
    $this->_mockSuccessResponse($msg);
    $this->call->detectHuman([], 25)->done(function($result) {
      $this->assertInstanceOf('SignalWire\Relay\Calling\Results\DetectResult', $result);
      $this->assertTrue($result->isSuccessful());
      $this->assertEquals($result->getType(), 'machine');
      $this->assertEmpty($result->getResult());
      $this->assertObjectHasAttribute('type', $result->getEvent()->payload);
      $this->assertObjectHasAttribute('params', $result->getEvent()->payload);
    });
    $this->calling->notificationHandler(self::$notificationMachineHuman);
    // $this->calling->notificationHandler(self::$notificationFaxFinished);
  }

  public function testDetectHumanFail(): void {
    $msg = $this->_detectMsg('machine');
    $this->_mockFailResponse($msg);
    $this->call->detectHuman([], 25)->done([$this, '__detectFailCheck']);
  }

  public function testDetectHumanAsyncSuccess(): void {
    $msg = $this->_detectMsg('machine', ['initial_timeout' => 5], 45);
    $this->_mockSuccessResponse($msg);
    $this->call->detectHumanAsync(['initial_timeout' => 5], 45)->done(function($action) {
      $this->assertInstanceOf('SignalWire\Relay\Calling\Actions\DetectAction', $action);
      $this->assertInstanceOf('SignalWire\Relay\Calling\Results\DetectResult', $action->getResult());
      $this->assertFalse($action->isCompleted());
      $this->calling->notificationHandler(self::$notificationMachineHuman);
      $this->assertTrue($action->isCompleted());
    });
  }

  public function testDetectHumanAsyncFail(): void {
    $msg = $this->_detectMsg('machine', ['initial_timeout' => 5], 45);
    $this->_mockFailResponse($msg);
    $this->call->detectHumanAsync(['initial_timeout' => 5], 45)->done([$this, '__detectAsyncFailCheck']);
  }

  public function testDetectMachineSuccess(): void {
    $msg = $this->_detectMsg('machine');
    $this->_mockSuccessResponse($msg);

    $this->call->detectMachine([], 25)->done(function($result) {
      $this->assertInstanceOf('SignalWire\Relay\Calling\Results\DetectResult', $result);
      $this->assertTrue($result->isSuccessful());
      $this->assertEquals($result->getType(), 'machine');
      $this->assertEquals($result->getResult(), 'HUMAN');
      $this->assertObjectHasAttribute('type', $result->getEvent()->payload);
      $this->assertObjectHasAttribute('params', $result->getEvent()->payload);
    });
    $this->calling->notificationHandler(self::$notificationMachineHuman);
    $this->calling->notificationHandler(self::$notificationMachineFinished);
  }

  public function testDetectMachineFail(): void {
    $msg = $this->_detectMsg('machine');
    $this->_mockFailResponse($msg);
    $this->call->detectMachine([], 25)->done([$this, '__detectFailCheck']);
  }

  public function testDetectMachineAsyncSuccess(): void {
    $msg = $this->_detectMsg('machine',['initial_timeout' => 4], 45);
    $this->_mockSuccessResponse($msg);

    $this->call->detectMachineAsync(['initial_timeout' => 4], 45)->done(function($action) {
      $this->assertInstanceOf('SignalWire\Relay\Calling\Actions\DetectAction', $action);
      $this->assertInstanceOf('SignalWire\Relay\Calling\Results\DetectResult', $action->getResult());
      $this->assertFalse($action->isCompleted());
      $this->calling->notificationHandler(self::$notificationMachineFinished);
      $this->assertTrue($action->isCompleted());
    });
  }

  public function testDetectMachineAsyncFail(): void {
    $msg = $this->_detectMsg('machine',['initial_timeout' => 4], 45);
    $this->_mockFailResponse($msg);
    $this->call->detectMachineAsync(['initial_timeout' => 4], 45)->done([$this, '__detectAsyncFailCheck']);
  }

  public function testDetectDigitSuccess(): void {
    $msg = $this->_detectMsg('digit');
    $this->_mockSuccessResponse($msg);

    $this->call->detectDigit(null, 25)->done(function($result) {
      $this->assertInstanceOf('SignalWire\Relay\Calling\Results\DetectResult', $result);
      $this->assertTrue($result->isSuccessful());
      $this->assertEquals($result->getType(), 'digit');
      $this->assertEquals($result->getResult(), '1#');
      $this->assertObjectHasAttribute('type', $result->getEvent()->payload);
      $this->assertObjectHasAttribute('params', $result->getEvent()->payload);
    });
    $this->calling->notificationHandler(self::$notificationDigitDTMF);
    $this->calling->notificationHandler(self::$notificationDigitFinished);
  }

  public function testDetectDigitFail(): void {
    $msg = $this->_detectMsg('digit');
    $this->_mockFailResponse($msg);
    $this->call->detectDigit(null, 25)->done([$this, '__detectFailCheck']);
  }

  public function testDetectDigitAsyncSuccess(): void {
    $msg = $this->_detectMsg('digit', ['digits' => '123'], 45);
    $this->_mockSuccessResponse($msg);

    $this->call->detectDigitAsync('123', 45)->done(function($action) {
      $this->assertInstanceOf('SignalWire\Relay\Calling\Actions\DetectAction', $action);
      $this->assertInstanceOf('SignalWire\Relay\Calling\Results\DetectResult', $action->getResult());
      $this->assertFalse($action->isCompleted());
      $this->calling->notificationHandler(self::$notificationDigitFinished);
      $this->assertTrue($action->isCompleted());
    });
  }

  public function testDetectDigitAsyncFail(): void {
    $msg = $this->_detectMsg('digit', ['digits' => '123'], 45);
    $this->_mockFailResponse($msg);
    $this->call->detectDigitAsync('123', 45)->done([$this, '__detectAsyncFailCheck']);
  }

  /**
   * Private to not repeat the same function for every sync fail
   */
  public function __detectFailCheck($result) {
    $this->assertInstanceOf('SignalWire\Relay\Calling\Results\DetectResult', $result);
    $this->assertFalse($result->isSuccessful());
  }

  /**
   * Private to not repeat the same function for every Async fail
   */
  public function __detectAsyncFailCheck($action) {
    $this->assertInstanceOf('SignalWire\Relay\Calling\Actions\DetectAction', $action);
    $this->assertTrue($action->isCompleted());
    $this->assertEquals($action->getState(), 'failed');
  }

  private function _detectMsg($type, $params = [], $timeout = 25) {
    return new Execute([
      'protocol' => 'signalwire_calling_proto',
      'method' => 'call.detect',
      'params' => [
        'call_id' => 'call-id',
        'node_id' => 'node-id',
        'control_id' => self::UUID,
        'detect' => ['type' => $type, 'params' => $params],
        'timeout' => $timeout
      ]
    ]);
  }
}
