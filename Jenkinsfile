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

    post {
        always {
            emailext(
                to: 'uprighttechsolutions@gmail.com',
                subject: "Jenkins Build: ${env.JOB_NAME} #${env.BUILD_NUMBER} - ${currentBuild.currentResult}",
                mimeType: 'text/html',
                body: """
                    <h2>Jenkins Test Result</h2>
                    <p><b>Job:</b> ${env.JOB_NAME}</p>
                    <p><b>Build:</b> #${env.BUILD_NUMBER}</p>
                    <p><b>Status:</b> ${currentBuild.currentResult}</p>
                """
            )
        }
    }
}