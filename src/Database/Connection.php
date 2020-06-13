<?php


namespace Suilven\MoviesFromPictures\Database;


class Connection
{
    const RELATIVE_PATH_FROM_PIC_DIR_TO_SQLITE_FILE = 'pics.db';


    /**
     * PDO instance
     * @var type
     */
    private $pdo;

    /**
     * return in instance of the PDO object that connects to the SQLite database
     * @return \PDO
     */
    public function connect($directory)
    {
        if ($this->pdo == null) {
            $this->pdo = new \PDO("sqlite:" . $directory . '/' . self::RELATIVE_PATH_FROM_PIC_DIR_TO_SQLITE_FILE);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        $this->createPhotosTableIfNotExists();
    }


    public function insertPhoto($path)
    {
        $splits = explode('/', $path);
        error_log('PATH:');
        error_log(print_r($splits, 1));
        $filename = array_pop($splits);

        if (!$this->photoExists($path)) {
            error_log('PHOTO DOES NOT EXIST IN DB: ' . $filename);

            $sql = 'INSERT INTO photos(filename) VALUES(:file)';
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':file', $filename);
            $stmt->execute();

            $id = $this->pdo->lastInsertId();
            error_log('ID: ' . $id);

        }
    }


    public function getPhotos() {
        $stmt = $this->pdo->query('SELECT id, filename, hash '
            . 'FROM photos');
        $photos = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            //error_log('ROW FOUND: ');
            $photos[] = [
                'id' => $row['id'],
                'filename' => $row['filename'],
                'hash' => $row['hash'],
            ];
        }
        return $photos;
    }

    public function getPhoto($path)
    {
        $splits = explode('/', $path);
        error_log('PATH:');
        error_log(print_r($splits, 1));
        $filename = array_pop($splits);
        error_log('SEARCHING FOR FILENAME=' . $filename);
        $sql = 'SELECT id, filename, hash '
            . 'FROM photos WHERE filename=:filename';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('filename', $filename);
        $stmt->execute();
        $row = $stmt->fetch();

        error_log('PHOTO ROW FOR ' . $path .': ' . print_r($row, 1));
        return $row;
    }


    public function photoExists($path)
    {
        error_log('PHOTO EXISTS? ' . $path);
        $photo = $this->getPhoto($path);
        $exists = isset($photo['filename']);
        return $exists;
    }


    private function createPhotosTableIfNotExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS photos (
            id   INTEGER PRIMARY KEY,
            filename TEXT    NOT NULL UNIQUE,
            hash TEXT
        );";
        $this->pdo->exec($sql);
    }
}