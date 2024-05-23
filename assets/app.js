import './bootstrap.js';
import $ from 'jquery';
import 'moment';
import 'jQuery-QueryBuilder';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import 'bootstrap';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'jQuery-QueryBuilder/dist/css/query-builder.default.min.css';
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

$(document).ready(function() {
    $('#query-builder').on('afterUpdateRuleValue.queryBuilder', function(e, rule) {
        if (rule.filter.plugin === 'datepicker') {
            rule.$el.find('.rule-value-container input').datepicker('update');
        }
    });

    $('#query-builder').queryBuilder({
        filters: [
            {
                id: 'name',
                label: 'Name',
                type: 'string'
            },
            {
                id: 'age',
                label: 'Age',
                type: 'integer'
            },
            {
                id: 'birthday',
                label: 'Birthday',
                type: 'date',
            }
        ],

        plugin: 'datepicker',
        plugin_config: {
            format: 'yyyy/mm/dd',
            todayBtn: 'linked',
            todayHighlight: true,
            autoclose: true
        }
    });

    $('#get-rules').on('click', function() {
        var rules = $('#query-builder').queryBuilder('getRules');
        if (!$.isEmptyObject(rules)) {
            // Send rules to backend
            $.ajax({
                url: '/process-rules',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(rules),
                success: function(response) {
                    console.log('Rules processed:', response);
                },
                error: function(err) {
                    console.error('Error processing rules:', err);
                }
            });
        } else {
            console.log('No rules defined.');
        }
    });
});
