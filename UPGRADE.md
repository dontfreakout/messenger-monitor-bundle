# Upgrade Guide

## 0.5.0

* Two new `bigint` columns were added to the `processed_messages` table:
  `wait_time` and `handle_time`. These are milliseconds. You will need to
  create a migration to add these columns to your database. They are not
  nullable so your migration will need to account for existing data. You
  can either truncate (purge) the `processed_messages` table or have your
  migration calculate these values based on the existing data.

  Here's a calculation example for MySQL:

  ```php
  use Doctrine\DBAL\Schema\Schema;
  use Doctrine\Migrations\AbstractMigration;

  final class VersionXXX extends AbstractMigration
  {
      public function getDescription(): string
      {
          return 'Add processed_messages.wait_time and handle_time columns';
      }

      public function up(Schema $schema): void
      {
          // Add the columns as nullable
          $this->addSql('ALTER TABLE processed_messages ADD wait_time BIGINT DEFAULT NULL, ADD handle_time BIGINT DEFAULT NULL');

          // set the times from existing data
          $this->addSql('UPDATE processed_messages SET wait_time = TIMESTAMPDIFF(SECOND, dispatched_at, received_at) * 1000, handle_time = TIMESTAMPDIFF(SECOND, received_at, finished_at) * 1000');

          // Make the columns not nullable
          $this->addSql('ALTER TABLE processed_messages CHANGE wait_time wait_time BIGINT NOT NULL, CHANGE handle_time handle_time BIGINT NOT NULL');
      }

      public function down(Schema $schema): void
      {
          $this->addSql('ALTER TABLE processed_messages DROP wait_time, DROP handle_time');
      }
  }
  ```
* `ProcessedMessage::timeInQueue()` now returns milliseconds instead of seconds.
* `ProcessedMessage::timeToHandle()` now returns milliseconds instead of seconds.
* `ProcessedMessage::timeToProcess()` now returns milliseconds instead of seconds.
* `Snapshot::averageWaitTime()` now returns milliseconds instead of seconds.
* `Snapshot::averageHandlingTime()` now returns milliseconds instead of seconds.
* `Snapshot::averageProcessingTime()` now returns milliseconds instead of seconds.
