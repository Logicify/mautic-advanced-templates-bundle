<?php

return [
    'name'        => 'Advanced Templates',
    'description' => 'Plugin extends default email template capabilities with TWIG block so you can use advanced scripting techniques like conditions, loops etc',
    'version'     => '1.0',
    'author'      => 'Dmitry Berezovsky',
    'services' => [
        'events' => [
            // Register any event listeners
            'mautic.plugin.advanced_templates.email.subscriber' => [
                'class'     => \MauticPlugin\MauticAdvancedTemplatesBundle\EventListener\EmailSubscriber::class,
                'arguments' => [
                    'mautic.plugin.advanced_templates.helper.template_processor'
                ],
            ],
        ],
        'other' => [
            // Template processor
            'mautic.plugin.advanced_templates.helper.template_processor' => [
                'class' => \MauticPlugin\MauticAdvancedTemplatesBundle\Helper\TemplateProcessor::class,
                'tag' => 'advanced_templates',
                'arguments' => [
                    'monolog.logger.mautic',
                ],
            ]
        ],
    ],
];