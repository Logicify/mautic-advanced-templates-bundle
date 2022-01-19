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
                    'mautic.plugin.advanced_templates.helper.template_processor',
                    'monolog.logger.mautic',                    
                    'mautic.plugin.advanced_templates.helper.form_submission'
                ]
                ],
            'mautic.plugin.advanced_templates.dwc.subscriber' => [
                'class'     => \MauticPlugin\MauticAdvancedTemplatesBundle\EventListener\DynamicContentSubscriber::class,
                'arguments' => [
                    'mautic.plugin.advanced_templates.helper.template_processor',
                    'monolog.logger.mautic',
                    'mautic.plugin.advanced_templates.helper.form_submission'
                ]
            ],
            'mautic.plugin.advanced_templates.page.subscriber' => [
                'class'     => \MauticPlugin\MauticAdvancedTemplatesBundle\EventListener\PageSubscriber::class,
                'arguments' => [
                    'mautic.plugin.advanced_templates.helper.template_processor',
                    'monolog.logger.mautic',
                    'mautic.plugin.advanced_templates.helper.form_submission',
                    'mautic.tracker.contact'
                ]
            ]                                  
        ],
        'other' => [
            // Template processor
            'mautic.plugin.advanced_templates.helper.template_processor' => [
                'class' => \MauticPlugin\MauticAdvancedTemplatesBundle\Helper\TemplateProcessor::class,
                'arguments' => [
                    'monolog.logger.mautic',
                    'mautic.plugin.advanced_templates.helper.twig_loader_dynamiccontent',
                    'mautic.plugin.advanced_templates.helper.feed_factory'
                ]
            ],
            'mautic.plugin.advanced_templates.helper.twig_loader_dynamiccontent' => [
                'class' => \MauticPlugin\MauticAdvancedTemplatesBundle\Helper\Twig_Loader_DynamicContent::class,
                'arguments' => [
                    'monolog.logger.mautic',
                    'mautic.model.factory'
                ]
            ],
            'mautic.plugin.advanced_templates.helper.feed_factory' => [
                'class' => \MauticPlugin\MauticAdvancedTemplatesBundle\Feed\FeedFactory::class,
                'arguments' => [
                    'mautic.plugin.advanced_templates.helper.feed_processor'
                ]
            ],
            'mautic.plugin.advanced_templates.helper.feed_processor' => [
                'class' => \MauticPlugin\MauticAdvancedTemplatesBundle\Feed\FeedProcessor::class,
                'arguments' => [
                    'mautic.lead.model.lead',
                ]
            ],
            'mautic.plugin.advanced_templates.helper.form_submission' => [
                'class' => \MauticPlugin\MauticAdvancedTemplatesBundle\Helper\FormSubmission::class,
                'arguments' => [
                    'doctrine.dbal.default_connection',
                ]
            ],            
        ]
    ]
];