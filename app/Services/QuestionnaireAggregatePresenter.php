<?php

declare(strict_types=1);

final class QuestionnaireAggregatePresenter
{
    public function __construct(
        private QuestionnaireResultPresenter $resultPresenter = new QuestionnaireResultPresenter()
    ) {
    }

    /**
     * @param list<mixed> $snapshots
     * @return array{responses_with_answers:int,charts:array<string, list<array<string, mixed>>>}
     */
    public function forSnapshots(array $snapshots): array
    {
        $responses = 0;
        $chartsByKey = [];
        foreach ($snapshots as $snapshot) {
            $charts = $this->resultPresenter->forResult(
                ['answers_snapshot' => $snapshot],
                null
            )['choice_charts'];
            if ($charts === []) continue;

            $responses++;
            foreach ($charts as $sectionKey => $sectionCharts) {
                foreach ($sectionCharts as $chart) {
                    $key = (string) $chart['key'];
                    if (!isset($chartsByKey[$sectionKey][$key])) {
                        $chartsByKey[$sectionKey][$key] = [
                            ...$chart,
                            'values' => array_fill(0, count($chart['labels']), 0),
                            'selected' => [],
                        ];
                    }
                    foreach ($chart['values'] as $index => $value) {
                        $chartsByKey[$sectionKey][$key]['values'][$index] += (int) $value;
                    }
                }
            }
        }

        return [
            'responses_with_answers' => $responses,
            'charts' => array_map('array_values', $chartsByKey),
        ];
    }
}
