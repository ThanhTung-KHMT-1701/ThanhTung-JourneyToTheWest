import NaiveBayes

# data = \
# [
#     ['Sunny',       'Hot',      'High',     'Weak',     'no'],
#     ['Sunny',       'Hot',      'High',     'Strong',   'no'],
#     ['Overcast',    'Hot',      'High',     'Weak',     'yes'],
#     ['Rain',        'Mild',     'High',     'Weak',     'yes'],
#     ['Rain',        'Cool',     'Normal',   'Weak',     'yes'],
#     ['Rain',        'Cool',     'Normal',   'Strong',   'no'],
#     ['Overcast',    'Cool',     'Normal',   'Strong',   'yes'],
#     ['Overcast',    'Mild',     'High',     'Weak',     'no'],
#     ['Sunny',       'Cool',     'Normal',   'Weak',     'yes'],
#     ['Rain',        'Mild',     'Normal',   'Weak',     'yes']
# ]

data = \
[
    ['youth',   'high',     'no',   'fair',         'no'],
    ['youth',   'high',     'no',   'excellent',    'no'],
    ['middle',  'high',     'no',   'fair',         'yes'],
    ['senior',  'medium',   'no',   'fair',         'yes'],
    ['senior',  'low',      'yes',  'fair',         'yes'],
    ['senior',  'low',      'yes',  'excellent',    'no'],
    ['middle',  'low',      'yes',  'excellent',    'yes'],
    ['youth',   'medium',   'no',   'fair',         'yes'],
    ['youth',   'low',      'yes',  'fair',         'yes'],
    ['senior',  'medium',   'yes',  'fair',         'yes'],
    ['middle',  'medium',   'no',   'excellent',    'yes'],
    ['youth',   'medium',   'yes',  'excellent',    'yes'],
    ['middle',  'high',     'yes',  'fair',         'yes'],
    ['senior',  'medium',   'no',   'excellent',    'no'],
    ['youth',   'low',      'no',   'fair',         'no']
]

data_input=['youth','medium', 'yes', 'excellent', None]

print(f"NaiveBayes_predict   :     {NaiveBayes.use.NaiveBayes_predict(data, data_input)}")