<?php
namespace RTech\RCarApi;

class Make {
  private $db;
  private $requestMethod;
  private $makeId;

  public function __construct($db, $requestMethod, $makeId)
  {
    $this->db = $db;
    $this->requestMethod = $requestMethod;
    $this->makeId = $makeId;
  }

  public function processRequest()
  {
    switch ($this->requestMethod) {
      case 'GET':
        if ($this->makeId) {
          $response = $this->getMake($this->makeId);
        } else {
          $response = $this->getAllMakes();
        };
        break;
      case 'POST':
        $response = $this->createMake();
        break;
      case 'PUT':
        $response = $this->updateMake($this->makeId);
        break;
      case 'DELETE':
        $response = $this->deleteMake($this->makeId);
        break;
      default:
        $response = $this->notFoundResponse();
        break;
    }
    header($response['status_code_header']);
    if ($response['body']) {
        echo $response['body'];
    }
  }

  private function getAllMakes()
  {
    $query = "
      SELECT
          *
      FROM
          makes;
    ";

    try {
      $statement = $this->db->query($query);
      $result = $statement->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }

    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode($result);
    return $response;
  }

  private function getMake($id)
  {
    $result = $this->find($id);
    if (! $result) {
        return $this->notFoundResponse();
    }
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode($result);
    return $response;
  }

  private function createMake()
  {
    $input = (array) json_decode(file_get_contents('php://input'), TRUE);
    if (! $this->validatePost($input)) {
      return $this->unprocessableEntityResponse();
    }

    $query = "
      INSERT INTO makes
          (make_id, make_display, make_is_common, make_country)
      VALUES
          (:make_id, :make_display, :make_is_common, :make_country);
    ";

    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array(
        'make_id' => $input['title'],
        'make_display'  => $input['body'],
        'make_is_common' => $input['author'],
        'make_country' => 'https://secure.gravatar.com/avatar/'.md5(strtolower($input['author'])).'.png?s=200',
      ));
      $statement->rowCount();
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }

    $response['status_code_header'] = 'HTTP/1.1 201 Created';
    $response['body'] = json_encode(array('message' => 'Post Created'));
    return $response;
  }

  private function updateMake($id)
  {
    $result = $this->find($id);
    if (! $result) {
      return $this->notFoundResponse();
    }
    $input = (array) json_decode(file_get_contents('php://input'), TRUE);
    if (! $this->validatePost($input)) {
      return $this->unprocessableEntityResponse();
    }

    $statement = "
      UPDATE post
      SET
        title = :title,
        body  = :body,
        author = :author,
        author_picture = :author_picture
      WHERE id = :id;
    ";

    try {
      $statement = $this->db->prepare($statement);
      $statement->execute(array(
        'id' => (int) $id,
        'title' => $input['title'],
        'body'  => $input['body'],
        'author' => $input['author'],
        'author_picture' => 'https://secure.gravatar.com/avatar/'.md5($input['author']).'.png?s=200',
      ));
      $statement->rowCount();
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode(array('message' => 'Post Updated!'));
    return $response;
  }

  private function deleteMake($id)
  {
    $result = $this->find($id);
    if (! $result) {
      return $this->notFoundResponse();
    }

    $query = "
      DELETE FROM post
      WHERE id = :id;
    ";

    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array('id' => $id));
      $statement->rowCount();
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
    $response['status_code_header'] = 'HTTP/1.1 200 OK';
    $response['body'] = json_encode(array('message' => 'Post Deleted!'));
    return $response;
  }

  public function find($id)
  {
    $query = "
      SELECT
          id, title, body, author, author_picture, created_at
      FROM
          post
      WHERE id = :id;
    ";

    try {
      $statement = $this->db->prepare($query);
      $statement->execute(array('id' => $id));
      $result = $statement->fetch(\PDO::FETCH_ASSOC);
      return $result;
    } catch (\PDOException $e) {
      exit($e->getMessage());
    }
  }

  private function validatePost($input)
  {
    if (! isset($input['title'])) {
      return false;
    }
    if (! isset($input['body'])) {
      return false;
    }

    return true;
  }

  private function unprocessableEntityResponse()
  {
    $response['status_code_header'] = 'HTTP/1.1 422 Unprocessable Entity';
    $response['body'] = json_encode([
      'error' => 'Invalid input'
    ]);
    return $response;
  }

  private function notFoundResponse()
  {
    $response['status_code_header'] = 'HTTP/1.1 404 Not Found';
    $response['body'] = null;
    return $response;
  }
}
