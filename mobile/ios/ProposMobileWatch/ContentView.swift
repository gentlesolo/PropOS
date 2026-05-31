import SwiftUI
import WatchConnectivity

// ── Data models ───────────────────────────────────────────────────────────────

struct WatchStats: Codable {
    let callsToday: Int
    let tasksToday: Int
    let tasksOverdue: Int
    let avgSentiment: Double
}

struct WatchTask: Identifiable, Codable {
    let id: Int
    let title: String
    let dueAt: String?
}

// ── Watch connectivity session handler ────────────────────────────────────────

class WatchSessionManager: NSObject, WCSessionDelegate, ObservableObject {
    @Published var stats: WatchStats? = nil
    @Published var tasks: [WatchTask] = []
    @Published var isReachable: Bool = false

    static let shared = WatchSessionManager()

    override init() {
        super.init()
        if WCSession.isSupported() {
            WCSession.default.delegate = self
            WCSession.default.activate()
        }
    }

    func session(_ session: WCSession, activationDidCompleteWith activationState: WCSessionActivationState, error: Error?) {
        DispatchQueue.main.async { self.isReachable = session.isReachable }
    }

    func session(_ session: WCSession, didReceiveApplicationContext applicationContext: [String: Any]) {
        DispatchQueue.main.async {
            if let data = applicationContext["stats"] as? Data,
               let decoded = try? JSONDecoder().decode(WatchStats.self, from: data) {
                self.stats = decoded
            }
            if let data = applicationContext["tasks"] as? Data,
               let decoded = try? JSONDecoder().decode([WatchTask].self, from: data) {
                self.tasks = decoded
            }
        }
    }

    func completeTask(id: Int) {
        WCSession.default.sendMessage(["action": "completeTask", "id": id], replyHandler: nil)
    }
}

// ── Main watch view ───────────────────────────────────────────────────────────

struct ContentView: View {
    @StateObject private var session = WatchSessionManager.shared
    @State private var showTasks = false

    var body: some View {
        TabView {
            // Stats page
            VStack(spacing: 6) {
                Text("PropOS")
                    .font(.headline)
                    .foregroundColor(.blue)

                if let stats = session.stats {
                    HStack {
                        StatTile(label: "Calls", value: "\(stats.callsToday)", color: .blue)
                        StatTile(label: "Tasks", value: "\(stats.tasksToday)", color: stats.tasksOverdue > 0 ? .red : .green)
                    }
                    HStack {
                        StatTile(label: "Overdue", value: "\(stats.tasksOverdue)", color: .orange)
                        StatTile(label: "Sentiment", value: String(format: "%.0f", stats.avgSentiment), color: .purple)
                    }
                } else {
                    Text("Syncing…")
                        .foregroundColor(.secondary)
                        .font(.caption)
                }
            }
            .padding(.horizontal, 4)

            // Tasks page
            List {
                ForEach(session.tasks.prefix(5)) { task in
                    Button(action: { session.completeTask(id: task.id) }) {
                        HStack {
                            Image(systemName: "circle")
                                .foregroundColor(.blue)
                                .font(.caption)
                            Text(task.title)
                                .font(.caption2)
                                .lineLimit(2)
                        }
                    }
                }
                if session.tasks.isEmpty {
                    Text("No tasks today")
                        .font(.caption2)
                        .foregroundColor(.secondary)
                }
            }
            .navigationTitle("Tasks")
        }
        .tabViewStyle(.page)
    }
}

struct StatTile: View {
    let label: String
    let value: String
    let color: Color

    var body: some View {
        VStack(spacing: 2) {
            Text(value)
                .font(.title3.bold())
                .foregroundColor(color)
            Text(label)
                .font(.caption2)
                .foregroundColor(.secondary)
        }
        .frame(maxWidth: .infinity)
        .padding(6)
        .background(Color.secondary.opacity(0.15))
        .cornerRadius(8)
    }
}
