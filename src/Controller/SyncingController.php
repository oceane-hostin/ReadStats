<?php

namespace App\Controller;

use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Reading;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\AuthorRepository;
use App\Repository\BookRepository;
use App\Repository\ReadingRepository;
use App\Repository\TagRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SyncingController extends AbstractController
{
    const USERNAME_PARAM_PATTERN = "{username}";
    const YEAR_PARAM_PATTERN = "{year}";
    const PROFIL_URL = "https://booknode.com/profil/".self::USERNAME_PARAM_PATTERN;
    const HISTORY_URL = "https://booknode.com/profil/".self::USERNAME_PARAM_PATTERN."/historique/".self::YEAR_PARAM_PATTERN;

    const MONTH_MAPPING = [
        1 => "janvier",
        2 => "février",
        3 => "mars",
        4 => "avril",
        5 => "mai",
        6 => "juin",
        7 => "juillet",
        8 => "août",
        9 => "septembre",
        10 => "octobre",
        11 => "novembre",
        12 => "décembre"
    ];

    const JSON_KEY_YEAR_DATA = "details";
    const JSON_KEY_MONTH_DATA = "reads";
    const JSON_KEY_MONTH_NAME = "month_name";
    const JSON_KEY_BOOK = "book";
    const JSON_KEY_BOOK_ID = "id";
    const JSON_KEY_BOOK_NAME = "name";
    const JSON_KEY_BOOK_AUTHORS = "authors";
    const JSON_KEY_BOOK_AUTHOR_ID = "idauteur";
    const JSON_KEY_BOOK_AUTHOR_FIRSTNAME = "_prenom";
    const JSON_KEY_BOOK_AUTHOR_LASTNAME = "_nom";
    const JSON_KEY_BOOK_TAGS = "themes";
    const JSON_KEY_BOOK_TAG_ID = "id";
    const JSON_KEY_BOOK_TAG_NAME = "nom";

    protected ?User $_currentUser;

    public function __construct(
        protected UserRepository $_userRepository,
        protected ReadingRepository $_readingRepository,
        protected AuthorRepository $_authorRepository,
        protected BookRepository $_bookRepository,
        protected TagRepository $_tagRepository,
        protected EntityManagerInterface $_entityManager,
        protected HttpClientInterface $_client,
        protected DecoderInterface $serializer
    ){}

    /**
     * @throws \Exception
     */
    #[Route('/sync/{username}')]
    public function syncUserReadings(string $username) : Response
    {
        $this->_currentUser = $this->_userRepository->findOneBy(["username" => $username]);
        if (empty($this->_currentUser) || !$this->_currentUser->getId()) {
            throw new \Exception("User doesn't exist");
        }

        if ($this->isBookNodeAccountExist()) {

            $readingToSave = $this->_getLastUserReading();
            if (!empty($readingToSave)) {
                $readingSynced = 0;
                $userReadings = $this->_userRepository->findUserReadingUniqueKey($this->_currentUser->getId());
                foreach ($readingToSave as $year => $yearReading) {
                    $yearReading = $yearReading[self::JSON_KEY_YEAR_DATA];
                    foreach ($yearReading as $monthReading) {
                        $month = $this->_convertMonthToNumber($monthReading[self::JSON_KEY_MONTH_NAME]);
                        $monthReading = $monthReading[self::JSON_KEY_MONTH_DATA];
                        foreach ($monthReading as $reading) {
                            $bookExternalId = $reading[self::JSON_KEY_BOOK][self::JSON_KEY_BOOK_ID];
                            $readingUniqueCheck = $year.$month.$bookExternalId;
                            $readingAlreadySaved = in_array($readingUniqueCheck, $userReadings);
                            if (!$readingAlreadySaved) {
                                $existingBook = $this->_bookRepository->findOneByExternalId($bookExternalId);

                                // create book if not already exist
                                $needSaveBook = false;
                                if (empty($existingBook)) {
                                    $existingBook = new Book();
                                    $existingBook->setExternalId($bookExternalId)
                                        ->setTitle($reading[self::JSON_KEY_BOOK][self::JSON_KEY_BOOK_NAME]);
                                    $needSaveBook = true;
                                }

                                // create and/or affect author to book
                                if ($existingBook->getAuthorCollection()->count() == 0) {
                                    $needSaveBook = true;
                                    foreach ($reading[self::JSON_KEY_BOOK][self::JSON_KEY_BOOK_AUTHORS] as $authorData) {
                                        $authorExternalId = $authorData[self::JSON_KEY_BOOK_AUTHOR_ID];
                                        $existingAuthor = $this->_authorRepository->findOneByExternalId($authorExternalId);
                                        if (empty($existingAuthor)) {
                                            $existingAuthor = new Author();
                                            $existingAuthor->setExternalId($authorExternalId)
                                                ->setFirstname($authorData[self::JSON_KEY_BOOK_AUTHOR_FIRSTNAME])
                                                ->setLastname($authorData[self::JSON_KEY_BOOK_AUTHOR_LASTNAME]);
                                            $this->_entityManager->persist($existingAuthor);
                                        }
                                        $existingBook->addAuthor($existingAuthor);
                                        unset($existingAuthor);
                                    }
                                    $this->_entityManager->flush();
                                }

                                // create and/or affect tags to book
                                if ($existingBook->getTagCollection()->count() == 0) {
                                    $isManga = false;
                                    $needSaveBook = true;
                                    foreach ($reading[self::JSON_KEY_BOOK][self::JSON_KEY_BOOK_TAGS] as $tagData) {
                                        $tagExternalId = $tagData[self::JSON_KEY_BOOK_TAG_ID];
                                        if (in_array($tagExternalId, Tag::MANGA_RELATED_TAG_IDS)) {
                                            $isManga = true;
                                        } else {
                                            $existingTag = $this->_tagRepository->findOneByExternalId($tagExternalId);
                                            if (empty($existingTag)) {
                                                $existingTag = new Tag();
                                                $existingTag->setExternalId($tagExternalId)
                                                    ->setName($tagData[self::JSON_KEY_BOOK_TAG_NAME]);
                                                $this->_entityManager->persist($existingTag);
                                            }
                                            $existingBook->addTag($existingTag);
                                            unset($existingTag);
                                        }
                                        $existingBook->setIsManga($isManga);
                                    }
                                    $this->_entityManager->flush();
                                }

                                if ($needSaveBook) {
                                    $this->_entityManager->persist($existingBook);
                                }

                                $reading = new Reading();
                                $reading->setUser($this->_currentUser)
                                    ->setMonth($month)
                                    ->setYear($year)
                                    ->setBook($existingBook)
                                    ->setIsBorrowed(true) // todo later do better
                                    ->setIsOwned(false);
                                $this->_entityManager->persist($reading);
                                unset($existingBook);
                                unset($reading);
                                $readingSynced++;
                            }
                        }
                    }
                }
            }
            $this->_entityManager->flush();
        } else {
            throw new \Exception("User is not linked to an booknode account");
        }

        return new Response($readingSynced." reading synced");
    }

    protected function _getLastUserReading(string $sinceDate = null) : array
    {
        if (empty($this->_currentUser)) {
            throw new \Exception("User wasn't found");
        }

        $historyUrl = $this->getBookNodeUrl("history");

        $currentYear = date("Y");
        if (empty($sinceDate)) {
            $sinceDate = $this->_currentUser->getFirstYear();
        }

        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Chrome/123.0.0.0"
            ]
        ];
        $context = stream_context_create($opts);

        $readingByYear = [];
        for ($i = $sinceDate; $i <= $currentYear; $i++) {
            $yearHistoryUrl = str_replace(self::YEAR_PARAM_PATTERN, $i, $historyUrl);
            $yearReading = file_get_contents($yearHistoryUrl, false, $context);

            if (!empty($yearReading)) {
                $readingByYear[$i] = $this->serializer->decode(
                    $yearReading,
                    JsonEncoder::FORMAT,
                    ["json_decode_associative" => true]
                );
            }
        }

        return $readingByYear;
    }

    /**
     * @throws \Exception
     */
    protected function isBookNodeAccountExist() : bool
    {
        try {
            $response = $this->_client->request(
                'GET',
                $this->getBookNodeUrl()
            );

            $statusCode = $response->getStatusCode();

            if ($statusCode == 200) {
                return true;
            }
        } catch (TransportExceptionInterface $e) {
        }

        return false;
    }

    protected function getBookNodeUrl(string $type = "profil") : string
    {
        switch ($type) {
            case "profil":
                $url = self::PROFIL_URL;
                break;
            case "history":
                $url = self::HISTORY_URL;
                break;
        }

        return str_replace(self::USERNAME_PARAM_PATTERN, $this->_currentUser->getUsername(), $url);
    }

    protected function _convertMonthToNumber(string $month) : int
    {
        return array_search($month, self::MONTH_MAPPING);
    }
}
