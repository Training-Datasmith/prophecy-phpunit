<?php

declare (strict_types=1);
namespace Prophecy\Php_Unit;

use Php_Unit\Framework\Assertion_Failed_Error;
use Php_Unit\Framework\Attributes\After;
use Php_Unit\Framework\Attributes\Post_Condition;
use Php_Unit\Framework\Test_Case;
use Prophecy\Exception\Doubler\Double_Exception;
use Prophecy\Exception\Doubler\Interface_Not_Found_Exception;
use Prophecy\Exception\Prediction\Prediction_Exception;
use Prophecy\Prophecy\Method_Prophecy;
use Prophecy\Prophecy\Object_Prophecy;
use Prophecy\Prophet;
/**
 * @mixin TestCase
 */
trait Prophecy_Trait
{
    /**
     * @var Prophet|null
     *
     * @internal
     */
    private $prophet;
    /**
     * @var bool
     *
     * @internal
     */
    private $prophecy_assertions_counted = false;
    /**
     * @throws DoubleException
     * @throws InterfaceNotFoundException
     *
     * @template T of object
     * @phpstan-param class-string<T>|null $classOrInterface
     * @phpstan-return ($classOrInterface is null ? ObjectProphecy<object> : ObjectProphecy<T>)
     *
     * @not-deprecated
     */
    protected function prophesize(?string $class_or_interface = null): Object_Prophecy
    {
        static $is_php_unit9;
        $is_php_unit9 ??= method_exists($this, 'recordDoubledType');
        if (!$is_php_unit9) {
            // PHPUnit 10.1
            $this->register_failure_type(Prediction_Exception::class);
        } elseif (\is_string($class_or_interface)) {
            // PHPUnit 9
            \assert($this instanceof Test_Case);
            $this->record_doubled_type($class_or_interface);
        }
        return $this->get_prophet()->prophesize($class_or_interface);
    }
    /**
     * @postCondition
     */
    #[Post_Condition]
    /**
     * Verifies all Prophecy predictions after each test and converts failures to PHPUnit assertions.
     *
     * Called automatically via `@postCondition` / `#[PostCondition]`. Checks that all
     * method prophecies were satisfied (i.e., all `shouldBeCalled()` and similar predictions
     * were met). On failure, wraps the `PredictionException` in an `AssertionFailedError`
     * so PHPUnit records it as a test failure rather than an error.
     *
     * @throws Assertion_Failed_Error When one or more prophecy predictions were not satisfied
     */
    protected function verify_prophecy_doubles(): void
    {
        if ($this->prophet === null) {
            return;
        }
        try {
            $this->prophet->check_predictions();
        } catch (Prediction_Exception $e) {
            throw new Assertion_Failed_Error($e->get_message());
        } finally {
            $this->count_prophecy_assertions();
        }
    }
    /**
     * @after
     */
    #[After]
    /**
     * Tears down the Prophet instance and counts any remaining prophecy assertions.
     *
     * Called automatically via `@after` / `#[After]`. Ensures assertion counts are
     * recorded even when a test fails before `verify_prophecy_doubles()` runs,
     * then nulls out the Prophet to prevent state leaking between tests.
     */
    protected function tear_down_prophecy(): void
    {
        if (null !== $this->prophet && !$this->prophecy_assertions_counted) {
            // Some Prophecy assertions may have been done in tests themselves even when a failure happened before checking mock objects.
            $this->count_prophecy_assertions();
        }
        $this->prophet = null;
    }
    /**
     * @internal
     */
    private function count_prophecy_assertions(): void
    {
        \assert($this instanceof Test_Case);
        \assert($this->prophet !== null);
        $this->prophecy_assertions_counted = true;
        foreach ($this->prophet->get_prophecies() as $object_prophecy) {
            foreach ($object_prophecy->get_method_prophecies() as $method_prophecies) {
                foreach ($method_prophecies as $method_prophecy) {
                    \assert($method_prophecy instanceof Method_Prophecy);
                    $this->add_to_assertion_count(\count($method_prophecy->get_checked_predictions()));
                }
            }
        }
    }
    /**
     * @internal
     */
    private function get_prophet(): Prophet
    {
        if ($this->prophet === null) {
            $this->prophet = new Prophet();
        }
        return $this->prophet;
    }
}