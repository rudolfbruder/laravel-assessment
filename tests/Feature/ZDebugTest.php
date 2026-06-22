<?php
namespace Tests\Feature;
use App\Domain\Tasks\Events\TaskCreated;
use App\Domain\Tasks\Projectors\TaskProjector;
use Tests\TestCase;
class ZDebugTest extends TestCase {
  public function test_debug(): void {
    $p = new TaskProjector();
    $e = new TaskCreated(1,['name'=>'x']);
    fwrite(STDERR, "handles=".var_export($p->handles($e), true)."\n");
    fwrite(STDERR, "methods=".implode(',', $p->handlesEvents())."\n");
    $this->assertTrue(true);
  }
}
