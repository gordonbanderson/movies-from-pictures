<?php declare(strict_types = 1);

namespace Suilven\MoviesFromPictures\Database;

class Connection
{
    public const RELATIVE_PATH_FROM_PIC_DIR_TO_SQLITE_FILE = 'pics.db';


    /**
     * PDO instance
     *
     * @var \PDO
     */
    private $pdo;


    /**
     * Connection constructor.
     *
     * @param string $directory path to directory of pics relative to root of the project */

    public function __construct(string $directory)
    {
        $this->pdo = new \PDO("sqlite:" . $directory . '/' . self::RELATIVE_PATH_FROM_PIC_DIR_TO_SQLITE_FILE);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    }


    public function connect(): void
    {
        $this->createPhotosTableIfNotExists();
    }


    /**
     * Insert a photo from the directory into the database. If it already exists skip the insertion
     *
     * @param string $path path to the file, e.g. pics/converted/DSC9823.jpg
     */
    public function insertPhoto(string $path): void
    {
        $splits = \explode('/', $path);
        \error_log('PATH:');
        \error_log(\print_r($splits, true));
        $filename = \array_pop($splits);

        if ($this->photoExists($path)) {
            return;
        }

        \error_log('PHOTO DOES NOT EXIST IN DB: ' . $filename);

        $sql = 'INSERT INTO photos(filename) VALUES(:file)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':file', $filename);
        $stmt->execute();

        $id = $this->pdo->lastInsertId();
        \error_log('ID: ' . $id);
    }


    /**
     * Get all of the photos from the database, i.e. all the images in this directory
     *
     * @return array<int, array<string, string|int|bool>>
     */
    public function getPhotos(): array
    {
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


    /**
     * @param string $path path to the file, e.g. pics/converted/DSC9823.jpg
     * @return array<string, string|int|bool> | bool - either an array representing the photo or false
     */
    public function getPhoto(string $path)
    {
        $splits = \explode('/', $path);
        \error_log('PATH:');
        \error_log(\print_r($splits, true));
        $filename = \array_pop($splits);
        \error_log('SEARCHING FOR FILENAME=' . $filename);
        $sql = 'SELECT id, filename, hash '
            . 'FROM photos WHERE filename=:filename';

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue('filename', $filename);
        $stmt->execute();
        $row = $stmt->fetch();

        \error_log('PHOTO ROW FOR ' . $path .': ' . \print_r($row, true));

        return $row;
    }


    /**
     * @param string $path path to the file, e.g. pics/converted/DSC9823.jpg
     * @return bool true if the photo exists, false if not
     */
    public function photoExists(string $path): bool
    {
        \error_log('PHOTO EXISTS? ' . $path);
        $photo = $this->getPhoto($path);

        return isset($photo['filename']);
    }


    private function createPhotosTableIfNotExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS photos (
            id   INTEGER PRIMARY KEY,
            filename TEXT    NOT NULL UNIQUE,
            hash TEXT
        );";
        $this->pdo->exec($sql);
    }
}
