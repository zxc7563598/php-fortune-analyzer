<?php

namespace Hejunjie\FortuneAnalyzer\Converter;

/**
 * 日期转换类
 * @package Hejunjie\FortuneAnalyzer\Converter
 */
class DateConverter
{
    protected static array $lunarMap = [];
    protected static array $solarMap = [];
    protected static array $solarTermsMap = [];

    /**
     * 通过阳历日期获取农历信息
     *
     * @param string|\DateTimeInterface $date 阳历日期，如 "2025-06-05" 或 DateTime 实例
     * 
     * @return string|null 
     */
    public static function getLunarFromSolar(string|\DateTimeInterface $date): ?string
    {
        if (empty(self::$lunarMap)) {
            self::$lunarMap = include __DIR__ . '/../Data/LunarMap.php';
        }
        // 支持字符串或 DateTime 对象
        $dt = $date instanceof \DateTimeInterface ? ($date)->format('Y-m-d') : (new \DateTime($date))->format('Y-m-d');
        return self::$lunarMap[$dt] ?? null;
    }

    /**
     * 通过农历日期获取阳历信息
     *
     * @param string|\DateTimeInterface $date 农历日期，如 "2025-06-05" 或 DateTime 实例
     * 
     * @return string|null 
     */
    public static function getSolarFromLunar(string|\DateTimeInterface $date): ?string
    {
        if (empty(self::$solarMap)) {
            self::$solarMap = include __DIR__ . '/../Data/SolarMap.php';
        }
        $dt = $date instanceof \DateTimeInterface ? ($date)->format('Y-m-d') : (new \DateTime($date))->format('Y-m-d');
        return self::$solarMap[$dt] ?? null;
    }

    /**
     * 根据年份(阳历日期)获取24节气
     * 
     * @param string|int $year 年份,阳历日期
     * 
     * @return array [['节气名称'=>'日期'],...]
     */
    public static function getSolarTermsByYear(string|int $year): array
    {

        if (empty(self::$solarTermsMap)) {
            self::$solarTermsMap = include __DIR__ . '/../Data/SolarTermsMap.php';
        }
        return self::$solarTermsMap[$year] ?? null;
    }

    /**
     * 标准化阳历日期为 "Y-m-d" 格式
     */
    protected static function normalizeDateKey(string $input): string
    {
        try {
            $dt = new \DateTime($input);
            return $dt->format('Y-m-d');
        } catch (\Exception $e) {
            return '';
        }
    }
}
