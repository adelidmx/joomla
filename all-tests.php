<?php
include ('config-test.php');
include ('autorun.php');

class AllTests extends TestSuite {
  function AllTests() {
    $this->TestSuite("All test files run now!");
    foreach (glob('test-*.php') as $file)
      $this->addTestFile($file);
  }
}
