pipeline {
    agent any

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