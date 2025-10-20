<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\LogService;
use App\Config\TableConfig;
use App\Config\Version;
use App\Repository\SensorReadRepository;
use App\Service\ChartDataService;
use App\Service\StatisticsAggregatorService;
use App\Service\TemplateRenderer;
use App\Service\WaterBalanceService;

class AquaponieController
{
    private SensorReadRepository $sensorReadRepo;
    private StatisticsAggregatorService $statsAggregator;
    private ChartDataService $chartDataService;
    private WaterBalanceService $waterBalanceService;
    private TemplateRenderer $renderer;
    private LogService $logger;

    public function __construct(
        SensorReadRepository $sensorReadRepo,
        StatisticsAggregatorService $statsAggregator,
        ChartDataService $chartDataService,
        WaterBalanceService $waterBalanceService,
        TemplateRenderer $renderer,
        LogService $logger
    ) {
        $this->sensorReadRepo = $sensorReadRepo;
        $this->statsAggregator = $statsAggregator;
        $this->chartDataService = $chartDataService;
        $this->waterBalanceService = $waterBalanceService;
        $this->renderer = $renderer;
        $this->logger = $logger;
    }

    /**
     * Affiche la page publique des données d'aquaponie
     */
    public function show(): void
    {
        try {
            $lastDate = $this->sensorReadRepo->getLastReadingDate();
            $defaultEndDate = $lastDate ?: date('Y-m-d H:i:s');
            $defaultStartDate = date('Y-m-d H:i:s', strtotime($defaultEndDate . ' -6 hours'));

        // Récupération des paramètres de période (nouveau format datetime-local ou ancien format séparé)
        [$startDate, $endDate] = $this->extractDateRange($defaultStartDate, $defaultEndDate);

        // Récupération des enregistrements
        $readings = $this->sensorReadRepo->fetchBetween($startDate, $endDate);
        $measure_count = count($readings);

        // Préparation des séries pour Highcharts
        $chartSeries = $this->chartDataService->prepareSeriesData($readings);
        $reading_time = $this->chartDataService->prepareTimestamps($readings);

        // Dernière lecture (pour jauges)
        $lastReading = $this->sensorReadRepo->getLastReadings();
        $lastReadingExtracted = $this->chartDataService->extractLastReadings($lastReading);

        // Statistiques agrégées
        $allStats = $this->statsAggregator->aggregateAllStats($startDate, $endDate);
        $statsFlattened = $this->statsAggregator->flattenForLegacy($allStats);

        // Calcul de la durée
        $duration = $this->calculateDuration($startDate, $endDate);

        // Métriques supplémentaires legacy
        // Export CSV si demandé
        if (isset($_POST['export_csv'])) {
            $this->exportCsv($startDate, $endDate);
            return;
        }

        // Injection dans le template
        if (isset($_GET['legacy'])) {
            // Mode legacy non supporté, rediriger vers Twig
            header('Location: /aquaponie');
            exit;
        }

        // Récupérer la version du firmware ESP32
        $firmwareVersion = $this->sensorReadRepo->getFirmwareVersion();

        // Calcul du bilan hydrique
        $waterBalance = $this->waterBalanceService->computeBalance($startDate, $endDate);

        // Environnement actuel
        $environment = TableConfig::getEnvironment();

        echo $this->renderer->render('aquaponie.twig', array_merge([
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'reading_time' => $reading_time,
            'measure_count' => $measure_count,
            'duration_str' => $duration,
            'version' => Version::getWithPrefix(),
            'firmware_version' => $firmwareVersion,
            'environment' => $environment,
        ], $chartSeries, [
            'last_reading_tempair' => $lastReadingExtracted['tempair'],
            'last_reading_tempeau' => $lastReadingExtracted['tempeau'],
            'last_reading_humi' => $lastReadingExtracted['humi'],
            'last_reading_lumi' => $lastReadingExtracted['lumi'],
            'last_reading_eauaqua' => $lastReadingExtracted['eauaqua'],
            'last_reading_eaureserve' => $lastReadingExtracted['eaureserve'],
            'last_reading_eaupota' => $lastReadingExtracted['eaupota'],
        ], $statsFlattened, $waterBalance));
        
        } catch (\Throwable $e) {
            $this->logger->error('AquaponieController::show failure', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            echo "ERREUR AquaponieController: " . $e->getMessage();
            exit(1);
        }
    }

    /**
     * Extrait la plage de dates depuis les paramètres POST
     */
    private function extractDateRange(string $defaultStart, string $defaultEnd): array
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return [$defaultStart, $defaultEnd];
        }

        // Nouveau format : datetime-local
        $startDatetimePost = filter_input(INPUT_POST, 'start_datetime');
        $endDatetimePost = filter_input(INPUT_POST, 'end_datetime');
        
        if ($startDatetimePost && $endDatetimePost) {
            return [
                str_replace('T', ' ', $startDatetimePost) . ':00',
                str_replace('T', ' ', $endDatetimePost) . ':00',
            ];
        }

        // Ancien format : date + time séparés
        $startDatePost = filter_input(INPUT_POST, 'start_date');
        $endDatePost = filter_input(INPUT_POST, 'end_date');
        $startTimePost = filter_input(INPUT_POST, 'start_time');
        $endTimePost = filter_input(INPUT_POST, 'end_time');
        
        if ($startDatePost && $endDatePost) {
            return [
                $startDatePost . ' ' . ($startTimePost ?: '00:00:00'),
                $endDatePost . ' ' . ($endTimePost ?: '23:59:59'),
            ];
        }

        return [$defaultStart, $defaultEnd];
    }

    /**
     * Calcule la durée lisible entre deux dates
     */
    private function calculateDuration(string $start, string $end): string
    {
        $duration_seconds = strtotime($end) - strtotime($start);
        $days = (int) floor($duration_seconds / 86400);
        $hours = (int) floor(($duration_seconds % 86400) / 3600);
        $minutes = (int) floor(($duration_seconds % 3600) / 60);
        $seconds = (int) ($duration_seconds % 60);
        
        return "$days jours, $hours heures, $minutes minutes, $seconds secondes";
    }

    /**
     * Gère l'export CSV
     */
    private function exportCsv(string $start, string $end): void
    {
        $tmpFile = sys_get_temp_dir() . '/sensor_export_' . time() . '.csv';
        $this->sensorReadRepo->exportCsv($start, $end, $tmpFile);
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="sensor_data_' . date('YmdHis') . '.csv"');
        readfile($tmpFile);
        unlink($tmpFile);
        exit;
    }
}
