const { useState, useEffect } = React;
const { Card, CardContent, CardHeader, CardTitle } = window.__UI_COMPONENTS__ || {};

const ClassDetailsDisplay = () => {
    const [classData, setClassData] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    
    // Get post ID from the div data attribute
    const postId = document.getElementById('class-details')?.dataset?.postId;

    useEffect(() => {
        const fetchClassDetails = async () => {
            try {
                const response = await fetch(`/wp-json/wp/v2/amelia_class/${postId}`);
                if (!response.ok) {
                    throw new Error('Failed to fetch class details');
                }
                const data = await response.json();
                
                // Get the class details from our custom REST field
                setClassData(data.class_details);
                setLoading(false);
            } catch (err) {
                setError(err.message);
                setLoading(false);
            }
        };

        if (postId) {
            fetchClassDetails();
        }
    }, [postId]);

    if (loading) {
        return <div className="p-4 text-center">Loading class details...</div>;
    }

    if (error) {
        return <div className="p-4 text-center text-red-600">Error: {error}</div>;
    }

    if (!classData) {
        return <div className="p-4 text-center">No class details found.</div>;
    }

    return (
        <div className="max-w-4xl mx-auto p-4 space-y-6">
            {/* Teacher Section */}
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:px-6">
                    <h3 className="text-lg font-medium">Teacher</h3>
                </div>
                <div className="px-4 py-5 sm:p-6">
                    {classData.teacher ? (
                        <div className="text-sm">
                            <span className="font-medium">Name: </span>
                            <span>{classData.teacher.name || 'Not assigned'}</span>
                        </div>
                    ) : (
                        <p>No teacher assigned</p>
                    )}
                </div>
            </div>

            {/* Schedule Section */}
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:px-6">
                    <h3 className="text-lg font-medium">Class Schedule</h3>
                </div>
                <div className="px-4 py-5 sm:p-6">
                    {classData.schedule ? (
                        <div className="space-y-2 text-sm">
                            <div>
                                <span className="font-medium">Time: </span>
                                <span>{classData.schedule.time || 'Not set'}</span>
                            </div>
                            <div>
                                <span className="font-medium">Duration: </span>
                                <span>{classData.schedule.duration || 'Not set'} minutes</span>
                            </div>
                            {classData.schedule.days && (
                                <div>
                                    <span className="font-medium">Days: </span>
                                    <div className="flex gap-2 mt-1">
                                        {classData.schedule.days.map((day, index) => (
                                            <span key={index} className="bg-gray-100 px-2 py-1 rounded">
                                                {day}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    ) : (
                        <p>No schedule set</p>
                    )}
                </div>
            </div>

            {/* Students Section */}
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:px-6">
                    <h3 className="text-lg font-medium">Students</h3>
                </div>
                <div className="px-4 py-5 sm:p-6">
                    {classData.students && classData.students.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Name
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Email
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {classData.students.map((student, index) => (
                                        <tr key={index}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                {student.name}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                {student.email}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-sm">No students enrolled</p>
                    )}
                </div>
            </div>

            {/* Sessions Section */}
            <div className="bg-white shadow rounded-lg">
                <div className="px-4 py-5 sm:px-6">
                    <h3 className="text-lg font-medium">Class Sessions</h3>
                </div>
                <div className="px-4 py-5 sm:p-6">
                    {classData.sessions && classData.sessions.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Date
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Status
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                                            Attendance
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {classData.sessions.map((session, index) => (
                                        <tr key={index}>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                {session.date}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                <span className={`px-2 py-1 rounded ${
                                                    session.status === 'completed' 
                                                        ? 'bg-green-100 text-green-800' 
                                                        : 'bg-blue-100 text-blue-800'
                                                }`}>
                                                    {session.status}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                {session.attended?.length > 0 
                                                    ? `${session.attended.length} students attended`
                                                    : 'No attendance yet'}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <p className="text-sm">No sessions scheduled</p>
                    )}
                </div>
            </div>
        </div>
    );
};

// Initialize the component when the DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const container = document.getElementById('class-details');
    if (container) {
        ReactDOM.render(<ClassDetailsDisplay />, container);
    }
});