<?php

namespace Hejunjie\FortuneAnalyzer;

use Hejunjie\FortuneAnalyzer\Calculator\BaZiCalculator;
use Hejunjie\FortuneAnalyzer\Calculator\WuXingCalculator;
use Hejunjie\FortuneAnalyzer\Converter\DateConverter;

class FortuneAnalyzer
{
    /**
     * 获取八字（四柱：年、月、日、时）
     */
    public static function analyzeFourPillars(string $datetime): array
    {
        return BaZiCalculator::getFourPillars($datetime);
    }

    /**
     * 获取年柱的详细信息（天干地支）
     */
    public static function getYearPillar(string $datetime): array
    {
        return BaZiCalculator::getYearPillar($datetime);
    }

    /**
     * 获取月柱的详细信息（天干地支）
     */
    public static function getMonthPillar(string $datetime): array
    {
        return BaZiCalculator::getMonthPillar($datetime);
    }

    /**
     * 获取日柱的详细信息（天干地支）
     */
    public static function getDayPillar(string $datetime): array
    {
        return BaZiCalculator::getDayPillar($datetime);
    }

    /**
     * 获取时柱的详细信息（天干地支）
     */
    public static function getHourPillar(string $datetime): array
    {
        return BaZiCalculator::getHourPillar($datetime);
    }

    /**
     * 获取五行统计信息（不含藏干）
     */
    public static function analyzeWuXingSimple(array $pillars): array
    {
        return WuXingCalculator::getWuXingWithoutHidden($pillars);
    }

    /**
     * 获取五行统计信息（含藏干）
     */
    public static function analyzeWuXingFull(array $pillars): array
    {
        return WuXingCalculator::getWuXingWithHidden($pillars);
    }

    /**
     * 获取每柱五行元素详情（天干地支和藏干对应五行）
     */
    public static function getWuXingBreakdown(array $pillars): array
    {
        return WuXingCalculator::getPillarDetailsSimple($pillars);
    }

    /**
     * 识别命盘五行局（如三会局、三合局等）
     */
    public static function detectWuXingJu(array $pillars): ?array
    {
        return WuXingCalculator::detectJu($pillars);
    }

    /**
     * 阳历转农历
     */
    public static function convertSolarToLunar(string $datetime): string
    {
        return DateConverter::getLunarFromSolar($datetime);
    }

    /**
     * 农历转阳历
     */
    public static function convertLunarToSolar(string $lunarDate, bool $isLeapMonth = false): string
    {
        return DateConverter::getSolarFromLunar($lunarDate, $isLeapMonth);
    }

    /**
     * 获取指定年份的 24 节气时间（秒级精度）
     */
    public static function getSolarTerms(int $year): array
    {
        return DateConverter::getSolarTermsByYear($year);
    }
}
