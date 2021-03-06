<?php

final class ManiphestTaskSubscriber extends ManiphestDAO {

  protected $taskPHID;
  protected $subscriberPHID;

  public function getConfiguration() {
    return array(
      self::CONFIG_IDS          => self::IDS_MANUAL,
      self::CONFIG_TIMESTAMPS   => false,
      self::CONFIG_COLUMN_SCHEMA => array(
        'id' => null,
      ),
      self::CONFIG_KEY_SCHEMA => array(
        'PRIMARY' => array(
          'columns' => array('subscriberPHID', 'taskPHID'),
          'unique' => true,
        ),
        'taskPHID' => array(
          'columns' => array('taskPHID', 'subscriberPHID'),
          'unique' => true,
        ),
      ),
    );
  }

  public static function updateTaskSubscribers(ManiphestTask $task) {
    $dao = new ManiphestTaskSubscriber();
    $conn = $dao->establishConnection('w');

    $sql = array();
    $subscribers = $task->getCCPHIDs();
    $subscribers[] = $task->getOwnerPHID();
    $subscribers = array_unique($subscribers);

    foreach ($subscribers as $subscriber_phid) {
      $sql[] = qsprintf(
        $conn,
        '(%s, %s)',
        $task->getPHID(),
        $subscriber_phid);
    }

    queryfx(
      $conn,
      'DELETE FROM %T WHERE taskPHID = %s',
      $dao->getTableName(),
      $task->getPHID());
    if ($sql) {
      queryfx(
        $conn,
        'INSERT INTO %T (taskPHID, subscriberPHID) VALUES %Q',
        $dao->getTableName(),
        implode(', ', $sql));
    }
  }

}
