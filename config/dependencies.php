<?php

declare(strict_types=1);

use App\Config\Database;
use App\Repository\BoardRepository;
use App\Repository\OutputRepository;
use App\Repository\SensorReadRepository;
use App\Repository\SensorRepository;
use App\Service\ChartDataService;
use App\Service\LogService;
use App\Service\NotificationService;
use App\Service\OutputService;
use App\Service\PumpService;
use App\Service\RealtimeDataService;
use App\Service\SensorDataService;
use App\Service\SensorStatisticsService;
use App\Service\StatisticsAggregatorService;
use App\Service\SystemHealthService;
use App\Service\TemplateRenderer;
use App\Service\TideAnalysisService;
use App\Service\WaterBalanceService;
use Psr\Container\ContainerInterface;

return [
    // ====================================================================
    // DATABASE CONNECTION (Singleton)
    // ====================================================================
    PDO::class => function (ContainerInterface $c) {
        return Database::getConnection();
    },

    // ====================================================================
    // REPOSITORIES
    // ====================================================================
    SensorReadRepository::class => function (ContainerInterface $c) {
        return new SensorReadRepository($c->get(PDO::class));
    },

    SensorRepository::class => function (ContainerInterface $c) {
        return new SensorRepository($c->get(PDO::class));
    },

    OutputRepository::class => function (ContainerInterface $c) {
        return new OutputRepository($c->get(PDO::class));
    },

    BoardRepository::class => function (ContainerInterface $c) {
        return new BoardRepository($c->get(PDO::class));
    },

    // ====================================================================
    // SERVICES
    // ====================================================================
    LogService::class => function (ContainerInterface $c) {
        return new LogService();
    },

    SensorStatisticsService::class => function (ContainerInterface $c) {
        return new SensorStatisticsService($c->get(PDO::class));
    },

    StatisticsAggregatorService::class => function (ContainerInterface $c) {
        return new StatisticsAggregatorService($c->get(SensorStatisticsService::class));
    },

    ChartDataService::class => function (ContainerInterface $c) {
        return new ChartDataService();
    },

    TideAnalysisService::class => function (ContainerInterface $c) {
        return new TideAnalysisService($c->get(SensorReadRepository::class));
    },

    WaterBalanceService::class => function (ContainerInterface $c) {
        return new WaterBalanceService($c->get(SensorReadRepository::class));
    },

    PumpService::class => function (ContainerInterface $c) {
        return new PumpService($c->get(PDO::class));
    },

    OutputService::class => function (ContainerInterface $c) {
        return new OutputService(
            $c->get(OutputRepository::class),
            $c->get(BoardRepository::class)
        );
    },

    SensorDataService::class => function (ContainerInterface $c) {
        return new SensorDataService(
            $c->get(PDO::class),
            $c->get(LogService::class)
        );
    },

    NotificationService::class => function (ContainerInterface $c) {
        return new NotificationService($c->get(LogService::class));
    },

    SystemHealthService::class => function (ContainerInterface $c) {
        return new SystemHealthService(
            $c->get(SensorReadRepository::class),
            $c->get(NotificationService::class),
            $c->get(LogService::class)
        );
    },

    TemplateRenderer::class => function (ContainerInterface $c) {
        return new TemplateRenderer();
    },

    RealtimeDataService::class => function (ContainerInterface $c) {
        return new RealtimeDataService(
            $c->get(SensorReadRepository::class),
            $c->get(OutputRepository::class),
            $c->get(PDO::class)
        );
    },

    // ====================================================================
    // CONTROLLERS (Définis explicitement pour éviter les problèmes d'autowiring)
    // ====================================================================
    \App\Controller\OutputController::class => function (ContainerInterface $c) {
        return new \App\Controller\OutputController(
            $c->get(\App\Service\OutputService::class),
            $c->get(\App\Service\TemplateRenderer::class),
            $c->get(\App\Repository\SensorReadRepository::class)
        );
    },

    \App\Controller\PostDataController::class => function (ContainerInterface $c) {
        return new \App\Controller\PostDataController();
    },

    \App\Controller\HeartbeatController::class => function (ContainerInterface $c) {
        return new \App\Controller\HeartbeatController();
    },

    \App\Controller\AquaponieController::class => function (ContainerInterface $c) {
        return new \App\Controller\AquaponieController(
            $c->get(\App\Repository\SensorReadRepository::class),
            $c->get(\App\Service\StatisticsAggregatorService::class),
            $c->get(\App\Service\ChartDataService::class),
            $c->get(\App\Service\WaterBalanceService::class)
        );
    },

    \App\Controller\DashboardController::class => function (ContainerInterface $c) {
        return new \App\Controller\DashboardController();
    },

    \App\Controller\ExportController::class => function (ContainerInterface $c) {
        return new \App\Controller\ExportController();
    },

    \App\Controller\TideStatsController::class => function (ContainerInterface $c) {
        return new \App\Controller\TideStatsController(
            $c->get(\App\Service\TideAnalysisService::class),
            $c->get(\App\Service\TemplateRenderer::class)
        );
    },

    \App\Controller\RealtimeApiController::class => function (ContainerInterface $c) {
        return new \App\Controller\RealtimeApiController(
            $c->get(\App\Service\RealtimeDataService::class)
        );
    },

    \App\Controller\HomeController::class => function (ContainerInterface $c) {
        return new \App\Controller\HomeController(
            $