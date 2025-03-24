import NaiveBayes

data = \
[
    ['Sunny',       'Hot',      'High',     'Weak',     'no'],
    ['Sunny',       'Hot',      'High',     'Strong',   'no'],
    ['Overcast',    'Hot',      'High',     'Weak',     'yes'],
    ['Rain',        'Mild',     'High',     'Weak',     'yes'],
    ['Rain',        'Cool',     'Normal',   'Weak',     'yes'],
    ['Rain',        'Cool',     'Normal',   'Strong',   'no'],
    ['Overcast',    'Cool',     'Normal',   'Strong',   'yes'],
    ['Overcast',    'Mild',     'High',     'Weak',     'no'],
    ['Sunny',       'Cool',     'Normal',   'Weak',     'yes'],
    ['Rain',        'Mild',     'Normal',   'Weak',     'yes']
]
data_input=['Sunny','Cool', 'High', 'Weak', None]

print(f"NaiveBayes_predict: {NaiveBayes.use.NaiveBayes_predict(data, data_input)}")