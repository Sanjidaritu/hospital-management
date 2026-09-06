pipeline {
    agent any

    triggers {
    pollSCM('H/5 * * * *')
}

    stages {
        stage('Run UI Tests') {
            steps {
                dir('UprightInsurance') {
                    bat 'mvn clean test'
                }
            }
        }
    }
}