
<?php
class TorqueDataConnectSubmitEdit extends APIBase {

  public function __construct($main, $action) {
    parent::__construct($main, $action);
  }

  public function execute() {
    $valid_group = TorqueDataConnectConfig::getValidGroup($this->getUser());
    global $wgTorqueDataConnectWikiKey;

    $newValues = $this->getParameter('newValues');
    $field = array_keys(json_decode($newValues, true))[0];
    $sheetName = $this->getParameter('sheetName');
    $key = $this->getParameter('key');
    $title = $this->getParameter('title');
    $log = new LogPage('torquedataconnect-datachanges',false);
    $log->addEntry('edit', Title::newFromText($title), null, $field);

    $url = 'http://localhost:5000/api/' .
      $sheetName .
      '/edit-record/' .
      $key;

    $ch = curl_init( $url );
    # Setup request to send json via POST.
    $payload = json_encode(
      array(
        "new_values" => $newValues,
        "wiki_key" => $wgTorqueDataConnectWikiKey,
        "group" => $valid_group,
      )
    );
    curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );
    curl_setopt( $ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    # Return response instead of printing.
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
    # Send request.
    $result = curl_exec($ch);
    curl_close($ch);

    return 201;
  }

  public function mustBePosted() {
    return true;
  }

  public function getAllowedParams() {
    return [
      "newValues" => [
        ApiBase::PARAM_TYPE => 'string',
        ApiBase::PARAM_REQUIRED => 'true'
      ],
      "sheetName" => [
        ApiBase::PARAM_TYPE => 'string',
        ApiBase::PARAM_REQUIRED => 'true'
      ],
      "key" => [
        ApiBase::PARAM_TYPE => 'string',
        ApiBase::PARAM_REQUIRED => 'true'
      ],
      "title" => [
        ApiBase::PARAM_TYPE => 'string',
        ApiBase::PARAM_REQUIRED => 'true'
      ],
    ];
  }
}
?>
