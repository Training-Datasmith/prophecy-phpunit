# Architecture: prophecy-phpunit

## Purpose

Integrates phpspec/prophecy with PHPUnit by providing a `ProphecyTrait` that automates
`Prophet` lifecycle management, prediction verification, and assertion counting.

## Directory Structure

```
src/
  Prophecy_Trait.php   # The single trait: prophesize(), verify_prophecy_doubles(), tear_down_prophecy()
fixtures/              # PHPUnit fixture test classes for the integration test suite
phpstan-fixtures/      # PHPStan analysis fixture demonstrating correct usage
tests/
  run_test.php         # Integration test runner using proc_open to run fixture tests
```

## Key Design Decisions

### Single-File Trait

The entire library is a single trait (`Prophecy_Trait`) mixed into a `TestCase`. This
avoids any inheritance constraints — users can extend any TestCase subclass. The trait
uses `@mixin TestCase` in PHPDoc to enable IDE type-checking of `$this->*` PHPUnit calls.

### Dual PHPUnit Version Support

The trait uses a runtime `method_exists($this, 'recordDoubledType')` check to distinguish
PHPUnit 9.x from PHPUnit 10.x+. This avoids compile-time incompatibilities while
supporting both versions from a single code path.

### Post-Condition Verification

`verify_prophecy_doubles()` is annotated with both `@postCondition` (PHPUnit 9 annotation)
and `#[PostCondition]` (PHPUnit 10 attribute) so that predictions are checked automatically
without any per-test teardown code.

### Assertion Counting

`count_prophecy_assertions()` calls `addToAssertionCount()` for each checked prediction,
ensuring PHPUnit's risky test detection (no assertions) works correctly even for tests
that use only prophecy predictions without explicit `assert*()` calls.

## Extension Points

None — this is a minimal glue library. For custom behavior, extend the trait methods.

## Dependency Flow

```
ProphecyTrait (mixed into TestCase)
  ├─ prophesize($class) → Prophet::prophesize() → ObjectProphecy
  ├─ verify_prophecy_doubles() [#PostCondition]
  │    └─ Prophet::checkPredictions() → PredictionException → AssertionFailedError
  └─ tear_down_prophecy() [#After]
       └─ count_prophecy_assertions() → addToAssertionCount()
```
