<?php

/**
 * Classe pour les opérations sécurisées sur la base de données
 * Remplace les requêtes concaténées par des prepared statements
 */

class DatabaseOperations
{

  private $conn;

  public function __construct($mysqli_connection)
  {
    $this->conn = $mysqli_connection;
  }

  /**
   * Effectue une requête SELECT avec paramètres
   * @param string $table Nom de la table
   * @param array $conditions Conditions WHERE (colonne => valeur)
   * @param string $select Colonnes à sélectionner (par défaut *)
   * @param string|null $orderBy Clause ORDER BY (ex: 'dateCreated DESC')
   * @return array|false Résultat de la requête
   */
  public function select($table, $conditions = [], $select = '*', $orderBy = null)
  {
    $where = '';
    $params = [];
    $types = '';

    if (!empty($conditions)) {
      $where_parts = [];
      foreach ($conditions as $col => $val) {
        $where_parts[] = "$col = ?";
        $params[] = $val;
        // Inférer le type
        $types .= is_int($val) ? 'i' : 's';
      }
      $where = ' WHERE ' . implode(' AND ', $where_parts);
    }

    $query = "SELECT $select FROM $table $where";

    if ($orderBy !== null) {
      $query .= " ORDER BY $orderBy";
    }
    $stmt = $this->conn->prepare($query);

    if (!$stmt) {
      return false;
    }

    if (!empty($params)) {
      $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
      $data[] = $row;
    }

    $stmt->close();
    return $data;
  }

  /**
   * Insère une ligne
   * @param string $table Nom de la table
   * @param array $data Colonnes et valeurs [colonne => valeur]
   * @return int|false ID inséré ou false en cas d'erreur
   */
  public function insert($table, $data)
  {
    if (empty($data)) return false;

    $columns = implode(',', array_keys($data));
    $placeholders = implode(',', array_fill(0, count($data), '?'));
    $values = array_values($data);

    $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
    $stmt = $this->conn->prepare($query);

    if (!$stmt) {
      return false;
    }

    // Inférer les types
    $types = '';
    foreach ($values as $val) {
      $types .= is_int($val) ? 'i' : 's';
    }

    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
      $insert_id = $stmt->insert_id;
      $stmt->close();
      return $insert_id;
    }

    $stmt->close();
    return false;
  }

  /**
   * Met à jour une ligne
   * @param string $table Nom de la table
   * @param array $data Données à mettre à jour [colonne => valeur]
   * @param array $where Conditions WHERE [colonne => valeur]
   * @return bool Succès ou non
   */
  public function update($table, $data, $where)
  {
    if (empty($data) || empty($where)) return false;

    $set_parts = [];
    $values = [];

    foreach ($data as $col => $val) {
      $set_parts[] = "$col = ?";
      $values[] = $val;
    }

    $where_parts = [];
    foreach ($where as $col => $val) {
      $where_parts[] = "$col = ?";
      $values[] = $val;
    }

    $set = implode(',', $set_parts);
    $where_clause = implode(' AND ', $where_parts);

    $query = "UPDATE $table SET $set WHERE $where_clause";
    $stmt = $this->conn->prepare($query);

    if (!$stmt) {
      return false;
    }

    // Inférer les types
    $types = '';
    foreach ($values as $val) {
      $types .= is_int($val) ? 'i' : 's';
    }

    $stmt->bind_param($types, ...$values);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
  }

  /**
   * Supprime une ou plusieurs lignes
   * @param string $table Nom de la table
   * @param array $where Conditions WHERE [colonne => valeur]
   * @return bool Succès ou non
   */
  public function delete($table, $where)
  {
    if (empty($where)) return false;

    $where_parts = [];
    $values = [];

    foreach ($where as $col => $val) {
      $where_parts[] = "$col = ?";
      $values[] = $val;
    }

    $where_clause = implode(' AND ', $where_parts);
    $query = "DELETE FROM $table WHERE $where_clause";

    $stmt = $this->conn->prepare($query);
    if (!$stmt) {
      return false;
    }

    // Inférer les types
    $types = '';
    foreach ($values as $val) {
      $types .= is_int($val) ? 'i' : 's';
    }

    $stmt->bind_param($types, ...$values);
    $result = $stmt->execute();
    $stmt->close();

    return $result;
  }

  /**
   * Compte les lignes
   * @param string $table Nom de la table
   * @param array $where Conditions WHERE [colonne => valeur]
   * @return int Nombre de lignes
   */
  public function count($table, $where = [])
  {
    $where_clause = '';
    $values = [];

    if (!empty($where)) {
      $where_parts = [];
      foreach ($where as $col => $val) {
        $where_parts[] = "$col = ?";
        $values[] = $val;
      }
      $where_clause = ' WHERE ' . implode(' AND ', $where_parts);
    }

    $query = "SELECT COUNT(*) as count FROM $table $where_clause";
    $stmt = $this->conn->prepare($query);

    if (!$stmt) {
      return 0;
    }

    if (!empty($values)) {
      $types = '';
      foreach ($values as $val) {
        $types .= is_int($val) ? 'i' : 's';
      }
      $stmt->bind_param($types, ...$values);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    return (int)$row['count'];
  }

  /**
   * Exécute une requête personnalisée (attention à l'injection SQL)
   * @param string $query Requête SQL
   * @param string $types Types de paramètres (i=int, s=string, etc)
   * @param array $params Paramètres à lier
   * @return mysqli_result|bool Résultat ou bool
   */
  public function execute($query, $types = '', $params = [])
  {
    $stmt = $this->conn->prepare($query);

    if (!$stmt) {
      return false;
    }

    if (!empty($params) && !empty($types)) {
      $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    // Vérifier si c'est une requête SELECT
    if (strpos(strtoupper(trim($query)), 'SELECT') === 0) {
      return $stmt->get_result();
    }

    $stmt->close();
    return true;
  }

  /**
   * Obtient le dernier message d'erreur
   */
  public function getError()
  {
    return $this->conn->error;
  }
}
