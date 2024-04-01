<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\StatsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class DashboardController extends AbstractController
{
    const COLOR_PRIMARY_RGB = "rgb(84, 160, 255)";
    const COLOR_PRIMARY_RGBA = "rgba(84, 160, 255, 0.2)";
    const COLOR_SECONDARY_RGB = "rgb(255, 107, 107)";
    const COLOR_SECONDARY_RGBA = "rgba(255, 107, 107, 0.2)";
    const COLOR_TERTIARY_RGB = "rgb(95, 39, 205)";
    const COLOR_TERTIARY_RGBA = "rgba(95, 39, 205, 0.2)";

    public function __construct(
        protected UserRepository $_userRepository,
        protected StatsService $_statsService,
        protected ChartBuilderInterface $_chartBuilder
    ) {}

    #[Route('/')]
    public function displayDashboard() : Response
    {
        $user = $this->_getUserDashboard();
        $userId = $user->getId();
        return $this->render('dashboard.html.twig', [
            "username" => $user->getUsername(),
            "author_number" => $this->_statsService->getUserCountDifferentAuthor($userId),
            "book_number" => $this->_statsService->getUserBookReadCount($userId),
            "manga_number" => $this->_statsService->getUserBookReadCount($userId, true),
            "avg_month" => $this->_statsService->getUserAverageReadCountByMonth($userId),
            "history_chart" => $this->_prepareHistoryChart($userId),
            "authors_top" => $this->_statsService->getUserAuthorsTop($userId, 7),
            "support_chart" => $this->_prepareSupportChart($userId),
            "series_chart" => $this->_prepareSeriesChart($userId),
            "tags_chart" => $this->_prepareTagsChart($userId, 15),
        ]);
    }

    protected function _prepareHistoryChart(int $userId)
    {
       $historyData = $this->_statsService->getUserReadingDetailsByMonth($userId);

        $labels = $dataSetGlobal = $dataSetBook = $dataSetManga = [];
        foreach ($historyData as $monthData) {
            $labels[] = $this->_getMonthNameFromNumber($monthData["month"])
                . " " . $monthData["year"];
            $dataSetBook[] = $monthData["book_count"];
            $dataSetManga[] = $monthData["manga_count"];
            $dataSetGlobal[] = $monthData["book_count"] + $monthData["manga_count"];
        }

        $chart = $this->_chartBuilder->createChart(Chart::TYPE_LINE);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Romans',
                    'backgroundColor' => self::COLOR_PRIMARY_RGBA,
                    'borderColor' => self::COLOR_PRIMARY_RGB,
                    'fill' => true,
                    'data' => $dataSetBook,
                ],
                [
                    'label' => 'Mangas',
                    'backgroundColor' => self::COLOR_SECONDARY_RGBA,
                    'borderColor' => self::COLOR_SECONDARY_RGB,
                    'fill' => true,
                    'data' => $dataSetManga,
                ],
                [
                    'label' => 'Total',
                    'borderColor' => self::COLOR_TERTIARY_RGB,
                    'borderDash' => [5, 5],
                    'data' => $dataSetGlobal,
                ],
            ],
        ]);

        /*$chart->setOptions([
            'scales' => [
                'y' => [
                    'suggestedMin' => 0,
                    'suggestedMax' => 100,
                ],
            ],
        ]);*/

        return $chart;
    }

    protected function _prepareSupportChart(int $userId)
    {
        $supportData = $this->_statsService->getUserSupportBookNumbers($userId);

        if (!array_key_exists(0, $supportData)) {
            return "";
        }

        $chart = $this->_chartBuilder->createChart(Chart::TYPE_PIE);

        $chart->setData([
            'labels' => ['Collection', 'Ebooks', 'Emprunts'],
            'datasets' => [
                [
                    'data' => [
                        $supportData[0]['owned_count'],
                        $supportData[0]['ebook_count'],
                        $supportData[0]['borrowed_count']
                    ],
                    'backgroundColor' => [
                        self::COLOR_PRIMARY_RGBA,
                        self::COLOR_SECONDARY_RGBA,
                        self::COLOR_TERTIARY_RGBA
                    ],
                    'borderColor' => [
                        self::COLOR_PRIMARY_RGB,
                        self::COLOR_SECONDARY_RGB,
                        self::COLOR_TERTIARY_RGB
                    ],
                ]
            ]
        ]);

        $chart->setOptions([
            'responsive' => true,
            //'maintainAspectRatio' => false
        ]);

        return $chart;
    }

    protected function _prepareSeriesChart(int $userId)
    {
        $seriesData = $this->_statsService->getUserSeriesBookNumbers($userId);
        if (!array_key_exists(0, $seriesData)) {
            return "";
        }

        $chart = $this->_chartBuilder->createChart(Chart::TYPE_PIE);

        $chart->setData([
            'labels' => ['Serie', 'Hors série'],
            'datasets' => [
                [
                    'data' => [
                        $seriesData[0]['serie_count'],
                        $seriesData[0]['out_serie_count']
                    ],
                    'backgroundColor' => [
                        self::COLOR_PRIMARY_RGBA,
                        self::COLOR_SECONDARY_RGBA,
                    ],
                    'borderColor' => [
                        self::COLOR_PRIMARY_RGB,
                        self::COLOR_SECONDARY_RGB,
                    ],
                ]
            ]
        ]);

        $chart->setOptions([
            'responsive' => true,
            //'maintainAspectRatio' => false
        ]);

        return $chart;
    }

    protected function _prepareTagsChart(int $userId, int $count = null)
    {
        $tagData = $this->_statsService->getUserTagsTop($userId, $count);

        $labels = $dataBook = $dataManga = [];
        foreach ($tagData as $tag) {
            $labels[] = $tag["name"];
            $dataBook[] = $tag["book_count"];
            $dataManga[] = $tag["manga_count"];
        }

        $chart = $this->_chartBuilder->createChart(Chart::TYPE_RADAR);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Romans',
                    'data' => $dataBook,
                    'backgroundColor' => self::COLOR_PRIMARY_RGBA,
                    'borderColor' => self::COLOR_PRIMARY_RGB,
                ],
                [
                    'label' => 'Mangas',
                    'data' => $dataManga,
                    'backgroundColor' => self::COLOR_SECONDARY_RGBA,
                    'borderColor' => self::COLOR_SECONDARY_RGB,
                ],
            ]
        ]);

        $chart->setOptions([
            'responsive' => true,
            //'maintainAspectRatio' => false
        ]);

        return $chart;
    }

    protected function _getMonthNameFromNumber(int $monthNumber) : string
    {
        return SyncingController::MONTH_MAPPING[$monthNumber];
    }

    protected function _getUserDashboard() : User
    {
        return $this->_userRepository->find(1); // todo later won't be forced
    }
}
